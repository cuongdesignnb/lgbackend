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
        {--purge : Delete ALL existing products, images, categories & brands before importing}';

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

        $successCount = 0;
        $skipCount = 0;
        $errorRows = [];

        $this->output->progressStart($totalRows);

        // ── Process each row ────────────────────────────────────────────
        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

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

                $price = $this->parsePrice($this->getValue($data, ['regular price', 'giá bán thường', 'giá gốc', '_regular_price']));
                $salePrice = $this->parsePrice($this->getValue($data, ['sale price', 'giá khuyến mãi', '_sale_price']));

                if ($salePrice <= 0 || ($salePrice >= $price && $price > 0)) {
                    $salePrice = null;
                }

                // Descriptions — strip HTML tags and WordPress shortcodes
                $shortDesc = $this->cleanHtml($this->getValue($data, ['short description', 'mô tả ngắn', 'post_excerpt']));
                $desc = $this->cleanHtml($this->getValue($data, ['description', 'mô tả', 'post_content']));

                $stock = max(0, (int) $this->getValue($data, ['stock', 'tồn kho', '_stock', 'in stock?']));
                $weight = (int) ((float) $this->getValue($data, ['weight (kg)', 'trọng lượng (kg)', '_weight', 'weight (lbs)']) * 1000); // kg to grams if needed

                // Category
                $categoryString = $this->getValue($data, ['categories', 'danh mục']);
                $categoryId = $this->getOrCreateCategory($categoryString);

                // Brand
                $brandString = $this->getValue($data, ['brand', 'thương hiệu', 'brands']);
                $brandId = $this->getOrCreateBrand($brandString);

                // Warranty (try to extract from short description or attributes)
                $warrantyMonths = null;
                $warrantyStr = $this->getValue($data, ['warranty', 'bảo hành']);
                if ($warrantyStr) {
                    preg_match('/(\d+)/', $warrantyStr, $m);
                    $warrantyMonths = isset($m[1]) ? (int) $m[1] : null;
                }

                // ── Create Product ──────────────────────────────────────
                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $name,
                        'slug' => $slug,
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'short_description' => $shortDesc ? Str::limit($shortDesc, 497) : null,
                        'description' => $desc,
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'stock_quantity' => $stock,
                        'is_active' => true,
                        'weight' => $weight > 0 ? $weight : null,
                        'warranty_months' => $warrantyMonths,
                        'specifications_text' => $this->extractSpecifications($data),
                        'meta_title' => Str::limit($name, 250),
                        'meta_description' => $shortDesc ? Str::limit($shortDesc, 250) : null,
                    ]
                );

                // ── Handle Images ───────────────────────────────────────
                $imageUrlsString = $this->getValue($data, ['images', 'hình ảnh']);
                if (!empty($imageUrlsString)) {
                    $this->processImages($product, $imageUrlsString);
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

        // Remove WordPress shortcodes like [caption id="..." align="..." width="..."]...[/caption]
        $text = preg_replace('/\[\/?\w+[^\]]*\]/', '', $html);

        // Remove HTML tags but keep text content
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Normalize whitespace: replace multiple spaces/newlines with single space
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text) ?: null;
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
}
