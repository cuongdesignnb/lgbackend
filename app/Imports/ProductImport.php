<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class ProductImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError
{
    use Importable, SkipsErrors;

    protected int $created = 0;
    protected int $updated = 0;
    protected array $skipped = [];

    /**
     * Map Excel row to Product model.
     * If a product with the same SKU exists, update it; otherwise create new.
     */
    public function model(array $row)
    {
        $sku = trim($row['sku'] ?? '');
        $name = trim($row['ten_san_pham'] ?? '');

        if (empty($sku) || empty($name)) {
            return null;
        }

        // Resolve category by name
        $categoryId = null;
        if (!empty($row['danh_muc'])) {
            $cat = Category::where('name', trim($row['danh_muc']))->first();
            if ($cat) {
                $categoryId = $cat->id;
            }
        }

        // Resolve brand by name
        $brandId = null;
        if (!empty($row['thuong_hieu'])) {
            $brand = Brand::where('name', trim($row['thuong_hieu']))->first();
            if ($brand) {
                $brandId = $brand->id;
            }
        }

        // Parse featured & active
        $isFeatured = in_array(mb_strtolower(trim($row['noi_bat'] ?? '')), ['có', 'co', '1', 'true', 'yes'], true);
        $isActive = !in_array(mb_strtolower(trim($row['trang_thai'] ?? 'đang bán')), ['ẩn', 'an', '0', 'false', 'no', 'inactive'], true);

        // Generate slug from name if not provided
        $slug = trim($row['slug'] ?? '') ?: Str::slug($name);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Product::where('slug', $slug)->where('sku', '!=', $sku)->exists()
            || Category::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'price' => floatval($row['gia_goc'] ?? 0),
            'sale_price' => !empty($row['gia_khuyen_mai']) ? floatval($row['gia_khuyen_mai']) : null,
            'cost_price' => !empty($row['gia_von']) ? floatval($row['gia_von']) : null,
            'stock_quantity' => intval($row['ton_kho'] ?? 0),
            'short_description' => $row['mo_ta_ngan'] ?? null,
            'is_featured' => $isFeatured,
            'is_active' => $isActive,
            'warranty_months' => !empty($row['bao_hanh_thang']) ? intval($row['bao_hanh_thang']) : null,
            'specifications_text' => $row['thong_so_ky_thuat'] ?? null,
        ];

        $existing = Product::withTrashed()->where('sku', $sku)->first();

        if ($existing) {
            // Restore if soft-deleted
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->update($data);
            $this->updated++;

            // Update primary image if provided
            if (!empty($row['anh_chinh'])) {
                $existing->images()->where('is_primary', true)->delete();
                $existing->images()->create([
                    'url' => trim($row['anh_chinh']),
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);
            }

            return null; // Already handled
        }

        $this->created++;

        $product = Product::create(array_merge($data, ['sku' => $sku]));

        // Create primary image if provided
        if (!empty($row['anh_chinh'])) {
            $product->images()->create([
                'url' => trim($row['anh_chinh']),
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }

        return null; // Already created manually
    }

    public function rules(): array
    {
        return [
            'sku' => 'required|string|max:100',
            'ten_san_pham' => 'required|string|max:255',
            'gia_goc' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Custom heading row mapping: Vietnamese headers → snake_case keys
     */
    public function customHeadingRow(): array
    {
        return [];
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }
}
