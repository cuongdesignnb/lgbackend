<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected ?int $categoryId;
    protected ?int $brandId;
    protected ?string $status;
    protected ?string $search;

    public function __construct(?int $categoryId = null, ?int $brandId = null, ?string $status = null, ?string $search = null)
    {
        $this->categoryId = $categoryId;
        $this->brandId = $brandId;
        $this->status = $status;
        $this->search = $search;
    }

    public function query()
    {
        $query = Product::query()
            ->with(['category', 'brand', 'primaryImage'])
            ->latest();

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }
        if ($this->brandId) {
            $query->where('brand_id', $this->brandId);
        }
        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%");
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tên sản phẩm',
            'SKU',
            'Slug',
            'Danh mục',
            'Thương hiệu',
            'Giá gốc',
            'Giá khuyến mãi',
            'Giá vốn',
            'Tồn kho',
            'Mô tả ngắn',
            'Nổi bật',
            'Trạng thái',
            'Bảo hành (tháng)',
            'Ảnh chính',
            'Thông số kỹ thuật',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku,
            $product->slug,
            $product->category?->name ?? '',
            $product->brand?->name ?? '',
            $product->price,
            $product->sale_price,
            $product->cost_price,
            $product->stock_quantity,
            $product->short_description,
            $product->is_featured ? 'Có' : 'Không',
            $product->is_active ? 'Đang bán' : 'Ẩn',
            $product->warranty_months,
            $product->primaryImage?->url ?? '',
            $product->specifications_text,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
            ],
        ];
    }
}
