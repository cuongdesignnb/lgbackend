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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:woo-products {file : Path to the CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from a WooCommerce CSV export';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return;
        }

        $this->info("Reading CSV file...");

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Could not open file.");
            return;
        }

        // Read the first row to get headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            $this->error("File is empty or invalid CSV.");
            return;
        }

        // Normalize headers
        $normalizedHeaders = array_map(function ($header) {
            // Remove BOM if exists
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
            return strtolower(trim($header));
        }, $headers);

        $rowCount = 0;
        $successCount = 0;
        $errorCount = 0;

        $this->output->progressStart(100); // Will advance row by row

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Ensure row matches headers count
                if (count($row) !== count($headers)) {
                    $row = array_pad($row, count($headers), '');
                }

                $data = array_combine($normalizedHeaders, $row);
                
                // Skip variations, only import simple/variable parents for now
                $type = $this->getValue($data, ['type', 'loại']);
                if ($type && in_array(strtolower($type), ['variation', 'biến thể'])) {
                    continue;
                }

                $name = $this->getValue($data, ['name', 'tên', 'post_title']);
                if (empty($name)) {
                    continue;
                }

                $sku = $this->getValue($data, ['sku', 'mã sp']);
                $price = $this->parsePrice($this->getValue($data, ['regular price', 'giá bán thường', '_regular_price']));
                $salePrice = $this->parsePrice($this->getValue($data, ['sale price', 'giá khuyến mãi', '_sale_price']));
                
                if ($salePrice >= $price && $price > 0) {
                    $salePrice = null;
                }

                $shortDesc = $this->getValue($data, ['short description', 'mô tả ngắn', 'post_excerpt']);
                $desc = $this->getValue($data, ['description', 'mô tả', 'post_content']);
                $stock = (int) $this->getValue($data, ['stock', 'tồn kho', '_stock']);
                $weight = (float) $this->getValue($data, ['weight (kg)', 'trọng lượng (kg)', '_weight']);
                
                $categoryString = $this->getValue($data, ['categories', 'danh mục']);
                $categoryId = $this->getOrCreateCategory($categoryString);

                $brandString = $this->getValue($data, ['brand', 'thương hiệu']);
                $brandId = $this->getOrCreateBrand($brandString);

                // Create or update Product
                $product = Product::updateOrCreate(
                    ['sku' => $sku ?: Str::random(8)], // Fallback if no SKU
                    [
                        'name' => $name,
                        'slug' => Str::slug($name) . '-' . uniqid(), // Avoid duplicates
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'short_description' => $shortDesc,
                        'description' => $desc,
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'stock_quantity' => $stock > 0 ? $stock : 0,
                        'is_active' => true,
                        'weight' => $weight,
                        'specifications_text' => $this->extractSpecifications($data),
                    ]
                );

                // Handle Images
                $imageUrlsString = $this->getValue($data, ['images', 'hình ảnh']);
                if (!empty($imageUrlsString)) {
                    $this->processImages($product, $imageUrlsString);
                }

                $successCount++;
                $this->output->progressAdvance();
                $rowCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error at row {$rowCount}: " . $e->getMessage());
        }

        fclose($handle);
        $this->output->progressFinish();

        $this->info("Import completed! Successfully imported/updated {$successCount} products.");
    }

    private function getValue(array $row, array $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            if (isset($row[$key])) {
                return trim($row[$key]);
            }
        }
        return null;
    }

    private function parsePrice($value)
    {
        if (empty($value)) return 0;
        // Remove everything except numbers
        $value = preg_replace('/[^0-9]/', '', $value);
        return (float) $value;
    }

    private function getOrCreateCategory($categoryString)
    {
        if (empty($categoryString)) return null;

        // Woo exports multiple categories separated by comma, and hierarchies by ">"
        // E.g., "Clothing > T-shirts, Men > Tops"
        // We will just take the first one for simplicity, or the deepest child of the first one
        
        $categories = explode(',', $categoryString);
        $firstPath = trim($categories[0]);
        
        $parts = array_map('trim', explode('>', $firstPath));
        $lastPartName = end($parts);
        
        if (empty($lastPartName)) return null;

        $category = Category::firstOrCreate(
            ['name' => $lastPartName],
            ['slug' => Str::slug($lastPartName), 'is_active' => true]
        );

        return $category->id;
    }

    private function getOrCreateBrand($brandString)
    {
        if (empty($brandString)) return null;

        $brand = Brand::firstOrCreate(
            ['name' => $brandString],
            ['slug' => Str::slug($brandString), 'is_active' => true]
        );

        return $brand->id;
    }

    private function extractSpecifications($data)
    {
        $specs = [];
        // Look for column names starting with 'attribute'
        // Example: 'attribute 1 name', 'attribute 1 value(s)'
        // We will loop up to 50 attributes
        for ($i = 1; $i <= 50; $i++) {
            $nameKey = "attribute {$i} name";
            $valueKey = "attribute {$i} value(s)";
            $valueKeyAlt = "attribute {$i} value";
            
            $nameKeyVn = "tên thuộc tính {$i}";
            $valueKeyVn = "giá trị thuộc tính {$i}";

            $name = $this->getValue($data, [$nameKey, $nameKeyVn]);
            $value = $this->getValue($data, [$valueKey, $valueKeyAlt, $valueKeyVn]);

            if ($name && $value) {
                // If it's brand, we might have extracted it, but we can also put it in specs
                $specs[] = "{$name}: {$value}";
            }
        }

        return implode("\n", $specs);
    }

    private function processImages($product, $imageUrlsString)
    {
        $urls = array_map('trim', explode(',', $imageUrlsString));
        
        foreach ($urls as $index => $url) {
            if (empty($url)) continue;
            
            // We will convert all images to webp
            $filename = 'products/' . $product->id . '/' . md5($url) . '.webp';

            try {
                // Download image if it doesn't exist
                if (!Storage::disk('public')->exists($filename)) {
                    $response = Http::timeout(10)->get($url);
                    if ($response->successful()) {
                        $imageContent = $response->body();
                        
                        // Convert to webp using GD
                        if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
                            $image = @imagecreatefromstring($imageContent);
                            if ($image !== false) {
                                ob_start();
                                // Handle transparency for PNG
                                imagepalettetotruecolor($image);
                                imagealphablending($image, true);
                                imagesavealpha($image, true);
                                
                                imagewebp($image, null, 80); // 80% quality
                                $webpContent = ob_get_clean();
                                imagedestroy($image);
                                
                                if (!empty($webpContent)) {
                                    $imageContent = $webpContent;
                                }
                            }
                        }

                        Storage::disk('public')->put($filename, $imageContent);
                    } else {
                        continue; // Skip if download fails
                    }
                }

                // Check if image record already exists for this product
                $exists = $product->images()->where('url', '/storage/' . $filename)->exists();
                if (!$exists) {
                    $product->images()->create([
                        'url' => '/storage/' . $filename,
                        'sort_order' => $index,
                        'is_primary' => ($index === 0),
                    ]);
                }
            } catch (\Exception $e) {
                // Log or silently fail for a single image error
                continue;
            }
        }
    }
}
