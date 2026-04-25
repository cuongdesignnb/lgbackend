<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ImportWooCommerceProducts extends Command
{
    protected $signature = 'import:woo-products
        {file : Path to the WooCommerce CSV export file}
        {--purge : Delete ALL existing products, images, categories & brands before importing}
        {--limit= : Limit number of products to import (for testing)}';

    protected $description = 'Import products from a WooCommerce CSV export file';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        // ── Purge existing data if requested ────────────────────────────
        if ($this->option('purge')) {
            if ($this->confirm('⚠️  This will DELETE all products, product images, categories, and brands. Continue?')) {
                $this->info('Purging existing data...');
                
                // Delete image files from storage
                $imagePaths = ProductImage::pluck('url')->toArray();
                foreach ($imagePaths as $url) {
                    // Convert URL back to storage path
                    $storagePath = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH) ?: $url);
                    Storage::disk('public')->delete($storagePath);
                }
                // Delete the products directory entirely
                Storage::disk('public')->deleteDirectory('products');

                ProductImage::query()->delete();
                Product::withTrashed()->forceDelete();
                Category::query()->delete();
                Brand::query()->delete();

                $this->info('✅ All existing data purged.');
            } else {
                $this->info('Aborted.');
                return 0;
            }
        }

        // ── Read CSV ────────────────────────────────────────────────────
        $this->info('Reading CSV file...');

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error('Could not open file.');
            return 1;
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            $this->error('File is empty or invalid CSV.');
            return 1;
        }

        // Normalize headers — only strip BOM, keep multibyte characters
        $normalizedHeaders = array_map(function ($header) {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
            return strtolower(trim($header));
        }, $headers);

        $this->info('Detected columns: ' . implode(', ', $normalizedHeaders));

        // Count rows for progress bar
        $totalRows = 0;
        while (fgetcsv($handle) !== false) {
            $totalRows++;
        }
        rewind($handle);
        fgetcsv($handle); // Skip header row again

        $this->info("Found {$totalRows} rows in CSV.");

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        if ($limit) {
            $this->info("⚡ Limiting to {$limit} products (test mode).");
        }

        $successCount = 0;
        $skipCount = 0;
        $errorRows = [];

        $this->output->progressStart($limit ?? $totalRows);

        // ── Process each row ────────────────────────────────────────────
        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Check limit
            if ($limit && $successCount >= $limit) break;

            try {
                // Pad row to match header length
                if (count($row) < count($normalizedHeaders)) {
                    $row = array_pad($row, count($normalizedHeaders), '');
                } elseif (count($row) > count($normalizedHeaders)) {
                    $row = array_slice($row, 0, count($normalizedHeaders));
                }

                $data = @array_combine($normalizedHeaders, $row);
                if ($data === false) {
                    $errorRows[] = "Row {$rowNumber}: column count mismatch";
                    $this->output->progressAdvance();
                    continue;
                }

                // Skip variations
                $type = $this->getValue($data, ['type', 'loại']);
                if ($type && in_array(strtolower(trim($type)), ['variation', 'biến thể'])) {
                    $skipCount++;
                    $this->output->progressAdvance();
                    continue;
                }

                $name = $this->getValue($data, ['name', 'tên', 'post_title']);
                if (empty($name)) {
                    $skipCount++;
                    $this->output->progressAdvance();
                    continue;
                }

                // ── Map fields ──────────────────────────────────────────
                $sku = $this->getValue($data, ['sku', 'mã sp', 'mã sản phẩm']) ?: ('WOO-' . Str::random(8));
                $slug = Str::slug($name);

                // Ensure unique slug
                $existingSlug = Product::withTrashed()->where('slug', $slug)->exists();
                if ($existingSlug) {
                    $slug = $slug . '-' . substr(md5($sku), 0, 6);
                }

                $price = $this->parsePrice($this->getValue($data, ['regular price', 'giá bán thường', 'giá gốc', '_regular_price', 'price', 'giá']));
                $salePrice = $this->parsePrice($this->getValue($data, ['sale price', 'giá khuyến mãi', '_sale_price']));

                // If regular price is 0 but sale_price has value, swap them
                if ($price <= 0 && $salePrice > 0) {
                    $price = $salePrice;
                    $salePrice = null;
                }

                // Clear sale price if it's invalid
                if ($salePrice !== null && ($salePrice <= 0 || $salePrice >= $price)) {
                    $salePrice = null;
                }

                // Descriptions — keep HTML for rich content, only strip WP shortcodes
                $shortDescRaw = $this->getValue($data, ['short description', 'mô tả ngắn', 'post_excerpt']);
                $shortDesc = $this->formatShortDescription($shortDescRaw);
                $descRaw = $this->getValue($data, ['description', 'mô tả', 'post_content']);

                // Extract specifications from description text
                $extractedSpecs = $this->extractSpecsFromDescription($descRaw);
                $desc = $this->sanitizeHtml($extractedSpecs['description']);

                $stock = max(0, (int) $this->getValue($data, ['stock', 'tồn kho', '_stock', 'in stock?']));
                $weight = (int) ((float) $this->getValue($data, ['weight (kg)', 'trọng lượng (kg)', '_weight', 'weight (lbs)']) * 1000); // kg to grams if needed

                // Category
                $categoryString = $this->getValue($data, ['categories', 'danh mục']);
                $categoryId = $this->getOrCreateCategory($categoryString);

                // Brand
                $brandString = $this->getValue($data, ['brand', 'thương hiệu', 'brands']);
                $brandId = $this->getOrCreateBrand($brandString);

                // Warranty (try to extract from short description or attributes)
                $warrantyMonths = 0;
                $warrantyStr = $this->getValue($data, ['warranty', 'bảo hành']);
                if ($warrantyStr) {
                    preg_match('/(\d+)/', $warrantyStr, $m);
                    $warrantyMonths = isset($m[1]) ? (int) $m[1] : 0;
                }
                // Also try to extract warranty from short description
                if ($warrantyMonths === 0 && $shortDescRaw) {
                    if (preg_match('/[Bb]ảo\s*hành\s*:?\s*(\d+)\s*(năm|tháng)/u', $shortDescRaw, $wm)) {
                        $warrantyMonths = (int) $wm[1];
                        if (mb_strtolower($wm[2]) === 'năm') {
                            $warrantyMonths *= 12;
                        }
                    }
                }

                // Merge specs from attributes + description
                $attrSpecs = $this->extractSpecifications($data);
                $descSpecs = $extractedSpecs['specs'];
                $allSpecs = trim(($attrSpecs ? $attrSpecs . "\n" : '') . ($descSpecs ?: ''));

                // ── Create Product ──────────────────────────────────────
                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $name,
                        'slug' => $slug,
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'short_description' => $shortDesc,
                        'description' => $desc,
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'stock_quantity' => $stock,
                        'is_active' => true,
                        'weight' => $weight > 0 ? $weight : null,
                        'warranty_months' => $warrantyMonths,
                        'specifications_text' => !empty($allSpecs) ? $allSpecs : null,
                        'meta_title' => Str::limit($name, 250),
                        'meta_description' => $shortDesc ? Str::limit(strip_tags($shortDesc), 250) : null,
                    ]
                );

                // ── Handle Images ───────────────────────────────────────
                $imageUrlsString = $this->getValue($data, ['images', 'hình ảnh']);
                if (!empty($imageUrlsString)) {
                    $this->processImages($product, $imageUrlsString);
                }

                // ── Handle description images ──────────────────────────
                if ($product->description) {
                    $updatedDesc = $this->processDescriptionImages($product, $product->description);
                    if ($updatedDesc !== $product->description) {
                        $product->update(['description' => $updatedDesc]);
                    }
                }

                $successCount++;
            } catch (\Exception $e) {
                $errorRows[] = "Row {$rowNumber}: " . $e->getMessage();
            }

            $this->output->progressAdvance();
        }

        fclose($handle);
        $this->output->progressFinish();

        // ── Summary ─────────────────────────────────────────────────────
        $this->newLine();
        $this->info("✅ Import completed!");
        $this->info("   ✓ Imported/updated: {$successCount} products");
        $this->info("   ⊘ Skipped (variations/empty): {$skipCount}");

        if (!empty($errorRows)) {
            $this->warn("   ✗ Errors: " . count($errorRows));
            foreach (array_slice($errorRows, 0, 20) as $err) {
                $this->line("     - {$err}");
            }
            if (count($errorRows) > 20) {
                $this->line("     ... and " . (count($errorRows) - 20) . " more.");
            }
        }

        return 0;
    }

    // ────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────

    private function getValue(array $row, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $key) {
            if (isset($row[$key]) && trim($row[$key]) !== '') {
                return trim($row[$key]);
            }
        }
        return null;
    }

    private function parsePrice(?string $value): float
    {
        if (empty($value)) return 0;
        // Handle Vietnamese format: 3.360.000 (dots as thousands) or 3,360,000
        // If the string has multiple dots, they are thousands separators
        if (substr_count($value, '.') > 1) {
            $value = str_replace('.', '', $value);
        }
        // If the string has commas followed by 3 digits, those are thousands separators  
        if (preg_match('/,\d{3}/', $value)) {
            $value = str_replace(',', '', $value);
        }
        // Remove everything except digits and dots
        $value = preg_replace('/[^0-9.]/', '', $value);
        return (float) $value;
    }

    /**
     * Strip HTML tags, WordPress shortcodes, and clean up whitespace.
     */
    private function cleanHtml(?string $html): ?string
    {
        if (empty($html)) return null;

        // Replace literal \n strings (from CSV) with actual newlines first
        $text = str_replace(['\n', '\r', '\t'], ["\n", "\r", "\t"], $html);

        // Remove WordPress shortcodes like [caption id="..." align="..." width="..."]...[/caption]
        $text = preg_replace('/\[\/?\w+[^\]]*\]/', '', $text);

        // Remove HTML tags but keep text content
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Normalize whitespace: replace multiple spaces/newlines with single space
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text) ?: null;
    }

    /**
     * Format WooCommerce short description.
     * Converts "Key : Value Key2 : Value2" into an HTML <ul> list.
     * Handles both single-line and multi-line formats.
     */
    private function formatShortDescription(?string $raw): ?string
    {
        if (empty($raw)) return null;

        // Replace literal \n from CSV
        $text = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $raw);

        // Strip any HTML tags that came from WooCommerce
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = trim($text);

        if (empty($text)) return null;

        // Known Vietnamese keys that appear in WooCommerce short descriptions
        $knownKeys = [
            'Mã SP', 'Mã sản phẩm', 'Hãng', 'Thương hiệu', 'Brand',
            'Bảo hành', 'Tình trạng', 'Xuất xứ', 'Chất liệu', 'Màu sắc',
            'Kích thước', 'Trọng lượng', 'Sản phẩm gồm', 'Bộ sản phẩm gồm',
            'Model', 'Dòng sản phẩm', 'Loại', 'Công suất', 'Điện áp',
        ];

        // Try to split single-line "Key : Value Key2 : Value2" format
        // Build regex pattern: (Key1|Key2|...)\s*:\s*
        $escapedKeys = array_map(function ($k) {
            return preg_quote($k, '/');
        }, $knownKeys);
        $keysPattern = implode('|', $escapedKeys);

        // Try to find key:value pairs
        $items = [];
        if (preg_match_all('/(' . $keysPattern . ')\s*:\s*/iu', $text, $matches, PREG_OFFSET_CAPTURE)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $key = $matches[1][$i][0];
                $startPos = $matches[0][$i][1] + strlen($matches[0][$i][0]);

                // Value goes until the next key or end of string
                if (isset($matches[0][$i + 1])) {
                    $endPos = $matches[0][$i + 1][1];
                } else {
                    $endPos = strlen($text);
                }

                $value = trim(substr($text, $startPos, $endPos - $startPos));
                if (!empty($value)) {
                    $items[] = ['key' => $key, 'val' => $value];
                }
            }
        }

        // If we found at least 2 key:value pairs, format as list
        if (count($items) >= 2) {
            $html = '<ul class="short-desc-list">';
            foreach ($items as $item) {
                $html .= '<li><strong>' . e($item['key']) . ':</strong> ' . e($item['val']) . '</li>';
            }
            $html .= '</ul>';
            return $html;
        }

        // Also try newline-separated key:value format
        $lines = preg_split('/\n+/', $text);
        $lines = array_values(array_filter(array_map('trim', $lines)));

        if (count($lines) >= 2) {
            $kvItems = [];
            foreach ($lines as $line) {
                if (preg_match('/^(.{2,40})\s*:\s*(.+)$/u', $line, $m)) {
                    $kvItems[] = ['key' => trim($m[1]), 'val' => trim($m[2])];
                }
            }
            if (count($kvItems) >= 2) {
                $html = '<ul class="short-desc-list">';
                foreach ($kvItems as $item) {
                    $html .= '<li><strong>' . e($item['key']) . ':</strong> ' . e($item['val']) . '</li>';
                }
                $html .= '</ul>';
                return $html;
            }
        }

        // Fallback: wrap in paragraph
        return '<p>' . e($text) . '</p>';
    }

    /**
     * Sanitize HTML: keep existing tags, or intelligently structure plain text
     * from WooCommerce CSV into rich HTML with headings, lists, and paragraphs.
     */
    private function sanitizeHtml(?string $html): ?string
    {
        if (empty($html)) return null;

        // Replace literal \n strings (from CSV) with actual newlines
        $text = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $html);

        // Remove WordPress shortcodes like [caption id="..." width="..."]...[/caption]
        $text = preg_replace('/\[\/?\w+[^\]]*\]/', '', $text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // If the text already has HTML block-level tags, just clean up and return
        if (preg_match('/<(p|div|h[1-6]|ul|ol|table|section|article)\b/i', $text)) {
            // Remove empty paragraphs
            $text = preg_replace('/<p>\s*<\/p>/', '', $text);
            return trim($text) ?: null;
        }

        // ── Plain text → structured HTML ──────────────────────────────
        // Split into lines
        $lines = preg_split('/\n+/', trim($text));
        $lines = array_values(array_filter(array_map('trim', $lines)));

        if (empty($lines)) return null;

        $output = [];
        $currentParagraph = [];

        // Heading keywords (Vietnamese)
        $headingPatterns = [
            'thông số kỹ thuật', 'tính năng', 'đặc điểm', 'ưu điểm',
            'ứng dụng', 'mô tả', 'thông tin', 'chi tiết', 'hướng dẫn',
            'phụ kiện', 'sản phẩm gồm', 'lưu ý', 'cam kết', 'bảo hành',
            'chính sách', 'liên hệ',
        ];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $lower = mb_strtolower($line);

            // Skip empty lines
            if (empty($line)) continue;

            // Skip URL lines (http, https, youtu.be, etc.)
            if (preg_match('/^https?\s*:|^\/\/|youtu\.?be|\.com\/|\.vn\//i', $line)) {
                continue;
            }

            // Detect heading: short line (< 80 chars) that matches keywords or ends with ':'
            $isHeading = false;
            if (mb_strlen($line) < 80) {
                foreach ($headingPatterns as $pattern) {
                    if (mb_strpos($lower, $pattern) !== false) {
                        $isHeading = true;
                        break;
                    }
                }
                // Also treat lines ending with ':' as headings (but NOT URLs)
                if (!$isHeading && preg_match('/^[^:]{5,60}:\s*$/', $line) && !preg_match('/https?/i', $line)) {
                    $isHeading = true;
                    $line = rtrim($line, ': ');
                }
            }

            if ($isHeading) {
                // Flush current paragraph
                if (!empty($currentParagraph)) {
                    $output[] = '<p>' . implode('<br>', $currentParagraph) . '</p>';
                    $currentParagraph = [];
                }
                $output[] = '<h3>' . $line . '</h3>';
                continue;
            }

            // Regular text — accumulate into paragraph, each line as a <br>
            $currentParagraph[] = $line;

            // If accumulated text is long enough, flush as paragraph
            if (count($currentParagraph) > 10) {
                $output[] = '<p>' . implode('<br>', $currentParagraph) . '</p>';
                $currentParagraph = [];
            }
        }

        // Flush remaining paragraph
        if (!empty($currentParagraph)) {
            $output[] = '<p>' . implode('<br>', $currentParagraph) . '</p>';
        }

        $result = implode("\n", $output);

        // Remove empty paragraphs
        $result = preg_replace('/<p>\s*<\/p>/', '', $result);

        return trim($result) ?: null;
    }

    private function getOrCreateCategory(?string $categoryString): ?int
    {
        if (empty($categoryString)) return null;

        // WooCommerce: "Parent > Child, Other Category"
        $categories = explode(',', $categoryString);
        $firstPath = trim($categories[0]);

        $parts = array_map('trim', explode('>', $firstPath));
        $parentId = null;

        // Create full hierarchy
        foreach ($parts as $partName) {
            if (empty($partName)) continue;

            $category = Category::firstOrCreate(
                ['name' => $partName, 'parent_id' => $parentId],
                ['slug' => Str::slug($partName) ?: Str::random(6), 'is_active' => true]
            );
            $parentId = $category->id;
        }

        return $parentId;
    }

    private function getOrCreateBrand(?string $brandString): ?int
    {
        if (empty($brandString)) return null;

        $brand = Brand::firstOrCreate(
            ['name' => trim($brandString)],
            ['slug' => Str::slug($brandString) ?: Str::random(6), 'is_active' => true]
        );

        return $brand->id;
    }

    private function extractSpecifications(array $data): ?string
    {
        $specs = [];

        for ($i = 1; $i <= 50; $i++) {
            $name = $this->getValue($data, [
                "attribute {$i} name",
                "tên thuộc tính {$i}",
            ]);
            $value = $this->getValue($data, [
                "attribute {$i} value(s)",
                "attribute {$i} value",
                "giá trị thuộc tính {$i}",
            ]);

            if ($name && $value) {
                $specs[] = "{$name}: {$value}";
            }
        }

        return !empty($specs) ? implode("\n", $specs) : null;
    }

    /**
     * Extract "Thông Số Kỹ Thuật" section from description text.
     * Returns ['description' => cleaned text, 'specs' => "Key: Value\nKey: Value" format]
     */
    private function extractSpecsFromDescription(?string $raw): array
    {
        $result = ['description' => $raw, 'specs' => null];
        if (empty($raw)) return $result;

        // Replace literal \n from CSV
        $text = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $raw);

        // Heading patterns that indicate a specs section (Vietnamese)
        $specHeadings = [
            'thông số kỹ thuật',
            'thông số sản phẩm',
            'thông tin kỹ thuật',
            'chi tiết kỹ thuật',
            'cấu hình',
        ];

        $headingPattern = implode('|', array_map(function ($h) {
            return preg_quote($h, '/');
        }, $specHeadings));

        // Try to find the specs section — look for the heading followed by key:value lines
        // Support both plain text and HTML headings
        $pattern = '/(?:^|\n)\s*(?:<h[1-6][^>]*>)?\s*(' . $headingPattern . ')\s*(?:<\/h[1-6]>)?\s*(?:\n|$)([\s\S]*?)(?=(?:\n\s*(?:<h[1-6][^>]*>)?\s*(?:' . $headingPattern . '|ứng dụng|ưu điểm|tính năng|mô tả|hướng dẫn|lưu ý|cam kết|liên hệ|đặc điểm)\s*(?:<\/h[1-6]>)?\s*\n)|\z)/iu';

        if (preg_match($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
            $fullMatch = $m[0][0];
            $specBody = $m[2][0];

            // Parse spec lines from the body
            $specLines = preg_split('/\n+/', trim($specBody));
            $specs = [];

            foreach ($specLines as $line) {
                $line = strip_tags(trim($line));
                if (empty($line)) continue;

                // Skip URL lines
                if (preg_match('/https?|youtu\.?be|\.com\/|\.vn\//i', $line)) continue;

                // Match "Key: Value" or "Key : Value"
                if (preg_match('/^(.{2,50}?)\s*:\s*(.+)$/u', $line, $kvMatch)) {
                    $key = trim($kvMatch[1]);
                    $val = trim($kvMatch[2]);
                    if (!empty($key) && !empty($val)) {
                        $specs[] = "{$key}: {$val}";
                    }
                }
            }

            if (!empty($specs)) {
                // Remove the specs section from description
                $cleanDesc = substr_replace($text, '', $m[0][1], strlen($fullMatch));
                $result['description'] = trim($cleanDesc);
                $result['specs'] = implode("\n", $specs);
            }
        }

        // If no heading match, try a simpler approach: look for consecutive key:value lines
        // that could be specs (at least 3 consecutive lines with key:value pattern)
        if (empty($result['specs'])) {
            $lines = preg_split('/\n+/', $text);
            $kvRuns = [];
            $currentRun = [];

            foreach ($lines as $i => $line) {
                $line = strip_tags(trim($line));
                // Skip URL lines
                if (preg_match('/https?|youtu\.?be|\.com\/|\.vn\//i', $line)) {
                    if (count($currentRun) >= 3) { $kvRuns[] = $currentRun; }
                    $currentRun = [];
                    continue;
                }
                if (preg_match('/^(.{2,40}?)\s*:\s*(.+)$/u', $line, $kvM)) {
                    $currentRun[] = ['line' => $i, 'key' => trim($kvM[1]), 'val' => trim($kvM[2])];
                } else {
                    if (count($currentRun) >= 3) {
                        $kvRuns[] = $currentRun;
                    }
                    $currentRun = [];
                }
            }
            if (count($currentRun) >= 3) {
                $kvRuns[] = $currentRun;
            }

            // Take the longest run as specs
            if (!empty($kvRuns)) {
                usort($kvRuns, fn($a, $b) => count($b) - count($a));
                $bestRun = $kvRuns[0];
                $specs = [];
                $linesToRemove = [];
                foreach ($bestRun as $item) {
                    $specs[] = "{$item['key']}: {$item['val']}";
                    $linesToRemove[] = $item['line'];
                }

                // Remove spec lines from description
                $allLines = preg_split('/\n/', $text);
                foreach ($linesToRemove as $li) {
                    unset($allLines[$li]);
                }
                $result['description'] = trim(implode("\n", $allLines));
                $result['specs'] = implode("\n", $specs);
            }
        }

        return $result;
    }

    private function processImages(Product $product, string $imageUrlsString): void
    {
        $urls = array_map('trim', explode(',', $imageUrlsString));

        foreach ($urls as $index => $url) {
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) continue;

            $storagePath = 'products/' . $product->id . '/' . md5($url) . '.webp';

            try {
                if (!Storage::disk('public')->exists($storagePath)) {
                    $response = Http::timeout(15)->get($url);
                    if (!$response->successful()) {
                        continue;
                    }

                    $imageContent = $response->body();

                    // Convert to WebP using GD
                    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
                        $image = @imagecreatefromstring($imageContent);
                        if ($image !== false) {
                            // Preserve transparency
                            imagepalettetotruecolor($image);
                            imagealphablending($image, true);
                            imagesavealpha($image, true);

                            ob_start();
                            imagewebp($image, null, 80);
                            $webpContent = ob_get_clean();
                            imagedestroy($image);

                            if (!empty($webpContent)) {
                                $imageContent = $webpContent;
                            }
                        }
                    }

                    Storage::disk('public')->put($storagePath, $imageContent);
                }

                // Generate proper full URL via Storage facade
                $fullUrl = Storage::disk('public')->url($storagePath);

                // Avoid duplicate image records
                $exists = $product->images()->where('url', $fullUrl)->exists();
                if (!$exists) {
                    $product->images()->create([
                        'url' => $fullUrl,
                        'alt_text' => $product->name,
                        'sort_order' => $index,
                        'is_primary' => ($index === 0),
                    ]);
                }
            } catch (\Exception $e) {
                // Skip single image failures silently
                continue;
            }
        }
    }

    /**
     * Download images found in description HTML, convert to WebP,
     * save to local storage, and replace URLs in description.
     */
    private function processDescriptionImages(Product $product, string $description): string
    {
        // Find all image URLs in the description (both <img src="..."> and bare URLs)
        $urls = [];

        // Match <img> tags
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $description, $imgMatches)) {
            $urls = array_merge($urls, $imgMatches[1]);
        }

        // Match bare image URLs (not already in img tags)
        if (preg_match_all('/(?<!["\'])https?:\/\/[^\s<>"\']+\.(?:jpg|jpeg|png|gif|webp|bmp)/i', $description, $bareMatches)) {
            $urls = array_merge($urls, $bareMatches[0]);
        }

        $urls = array_unique(array_filter($urls));
        if (empty($urls)) return $description;

        foreach ($urls as $originalUrl) {
            // Skip already-local URLs
            if (str_contains($originalUrl, '/storage/') || !str_contains($originalUrl, 'http')) {
                continue;
            }

            try {
                $storagePath = 'products/' . $product->id . '/desc/' . md5($originalUrl) . '.webp';

                // Skip if already downloaded
                if (Storage::disk('public')->exists($storagePath)) {
                    $fullUrl = rtrim(config('app.url'), '/') . Storage::url($storagePath);
                    $description = str_replace($originalUrl, $fullUrl, $description);
                    continue;
                }

                $response = Http::timeout(15)->get($originalUrl);
                if (!$response->successful()) continue;

                $imageContent = $response->body();

                // Convert to WebP using GD
                if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
                    $image = @imagecreatefromstring($imageContent);
                    if ($image !== false) {
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);

                        ob_start();
                        imagewebp($image, null, 80);
                        $webpContent = ob_get_clean();
                        imagedestroy($image);

                        if (!empty($webpContent) && strlen($webpContent) > 100) {
                            $imageContent = $webpContent;
                        }
                    }
                }

                Storage::disk('public')->put($storagePath, $imageContent);
                $fullUrl = rtrim(config('app.url'), '/') . Storage::url($storagePath);
                $description = str_replace($originalUrl, $fullUrl, $description);
            } catch (\Exception $e) {
                // Skip failed image downloads
                continue;
            }
        }

        return $description;
    }
}
