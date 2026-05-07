<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\SpecificationKey;
use App\Exports\ProductExport;
use App\Imports\ProductImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'primaryImage'])
            ->latest();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->status) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $products = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => Category::where('is_active', true)->get(),
            'brands' => Brand::where('is_active', true)->get(),
            'filters' => $request->only(['search', 'category_id', 'brand_id', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Create', [
            'categories' => Category::where('is_active', true)->get(),
            'brands' => Brand::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products',
            'sku' => 'required|string|max:100|unique:products',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'warranty_months' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            'specifications_text' => 'nullable|string',
        ]);

        if (Category::where('slug', $validated['slug'])->exists()) {
            return back()->withErrors(['slug' => 'Slug "' . $validated['slug'] . '" đã được sử dụng bởi một danh mục.'])->withInput();
        }

        $productData = collect($validated)->except(['thumbnail', 'gallery'])->toArray();
        $product = Product::create($productData);

        $sortOrder = 0;
        if (!empty($validated['thumbnail'])) {
            $product->images()->create(['url' => $validated['thumbnail'], 'is_primary' => true, 'sort_order' => $sortOrder++]);
        }
        if (!empty($validated['gallery'])) {
            foreach ($validated['gallery'] as $url) {
                if ($url !== ($validated['thumbnail'] ?? '')) {
                    $product->images()->create(['url' => $url, 'is_primary' => false, 'sort_order' => $sortOrder++]);
                }
            }
        }

        return redirect()->route('admin.products.index')->with('success', 'Tạo sản phẩm thành công');
    }

    public function edit(Product $product)
    {
        $product->load(['category', 'brand', 'images', 'specifications.specificationKey']);

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->get(),
            'brands' => Brand::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,' . $product->id,
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'warranty_months' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            'specifications_text' => 'nullable|string',
        ]);

        if (Category::where('slug', $validated['slug'])->exists()) {
            return back()->withErrors(['slug' => 'Slug "' . $validated['slug'] . '" đã được sử dụng bởi một danh mục.'])->withInput();
        }

        $productData = collect($validated)->except(['thumbnail', 'gallery'])->toArray();
        $product->update($productData);

        $product->images()->delete();
        $sortOrder = 0;
        if (!empty($validated['thumbnail'])) {
            $product->images()->create(['url' => $validated['thumbnail'], 'is_primary' => true, 'sort_order' => $sortOrder++]);
        }
        if (!empty($validated['gallery'])) {
            foreach ($validated['gallery'] as $url) {
                if ($url !== ($validated['thumbnail'] ?? '')) {
                    $product->images()->create(['url' => $url, 'is_primary' => false, 'sort_order' => $sortOrder++]);
                }
            }
        }

        return redirect()->route('admin.products.index')->with('success', 'Cập nhật sản phẩm thành công');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Xóa sản phẩm thành công');
    }

    /**
     * Export products to Excel (.xlsx)
     */
    public function export(Request $request)
    {
        $fileName = 'san-pham-' . date('Y-m-d-His') . '.xlsx';

        return Excel::download(
            new ProductExport(
                $request->input('category_id') ? (int) $request->input('category_id') : null,
                $request->input('brand_id') ? (int) $request->input('brand_id') : null,
                $request->input('status'),
                $request->input('search'),
            ),
            $fileName
        );
    }

    /**
     * Import products from Excel/CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new ProductImport();
            Excel::import($import, $request->file('file'));

            $created = $import->getCreatedCount();
            $updated = $import->getUpdatedCount();
            $errors = $import->errors();
            $errorCount = count($errors);

            $msg = "Nhập Excel thành công: {$created} mới, {$updated} cập nhật.";
            if ($errorCount > 0) {
                $msg .= " ({$errorCount} dòng lỗi bỏ qua)";
            }

            return redirect()->route('admin.products.index')->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Lỗi nhập Excel: ' . $e->getMessage());
        }
    }

    /**
     * Download import template (.xlsx)
     */
    public function importTemplate()
    {
        $headers = [
            'SKU', 'Tên sản phẩm', 'Slug', 'Danh mục', 'Thương hiệu',
            'Giá gốc', 'Giá khuyến mãi', 'Giá vốn', 'Tồn kho',
            'Mô tả ngắn', 'Nổi bật', 'Trạng thái', 'Bảo hành (tháng)',
            'Ảnh chính', 'Thông số kỹ thuật',
        ];

        $example = [
            'SP001', 'Ổ cắm ray trượt LG-R300', 'o-cam-ray-truot-lg-r300',
            'Ổ cắm ray trượt', 'LG Tech', '350000', '299000', '200000', '100',
            'Ổ cắm ray trượt cao cấp', 'Có', 'Đang bán', '24',
            'https://example.com/image.jpg', "Công suất: 3500W\nSố ổ cắm: 5",
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mẫu nhập sản phẩm');

        foreach ($headers as $col => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
        }

        foreach ($example as $col => $val) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '2';
            $sheet->setCellValue($cell, $val);
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'],
            ],
        ]);

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col)
            )->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempPath = storage_path('app/mau-nhap-san-pham.xlsx');
        $writer->save($tempPath);

        return response()->download($tempPath, 'mau-nhap-san-pham.xlsx')->deleteFileAfterSend(true);
    }
    /**
     * Quick update product fields (price, stock, status, featured) without touching images/specs.
     */
    public function quickUpdate(Request $request, Product $product)
    {
        $validated = $request->validate([
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'sometimes|nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,inactive,out_of_stock',
        ]);

        // Map status to is_active if provided
        if (isset($validated['status'])) {
            $validated['is_active'] = $validated['status'] === 'active';
            unset($validated['status']);
        }

        $product->update($validated);

        return back()->with('success', "Cập nhật nhanh \"{$product->name}\" thành công");
    }
}
