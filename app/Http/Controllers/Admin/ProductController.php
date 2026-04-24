<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\SpecificationKey;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            // Images
            'thumbnail' => 'nullable|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            // Specifications
            'specifications_text' => 'nullable|string',
        ]);

        // Check slug collision with categories
        if (Category::where('slug', $validated['slug'])->exists()) {
            return back()->withErrors(['slug' => 'Slug "' . $validated['slug'] . '" đã được sử dụng bởi một danh mục.'])->withInput();
        }

        $productData = collect($validated)->except(['thumbnail', 'gallery'])->toArray();
        $product = Product::create($productData);

        // Save images
        $sortOrder = 0;
        if (!empty($validated['thumbnail'])) {
            $product->images()->create([
                'url' => $validated['thumbnail'],
                'is_primary' => true,
                'sort_order' => $sortOrder++,
            ]);
        }
        if (!empty($validated['gallery'])) {
            foreach ($validated['gallery'] as $url) {
                if ($url !== ($validated['thumbnail'] ?? '')) {
                    $product->images()->create([
                        'url' => $url,
                        'is_primary' => false,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }



        return redirect()->route('admin.products.index')
            ->with('success', 'Tạo sản phẩm thành công');
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
            // Images
            'thumbnail' => 'nullable|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            // Specifications
            'specifications_text' => 'nullable|string',
        ]);

        // Check slug collision with categories
        if (Category::where('slug', $validated['slug'])->exists()) {
            return back()->withErrors(['slug' => 'Slug "' . $validated['slug'] . '" đã được sử dụng bởi một danh mục.'])->withInput();
        }

        $productData = collect($validated)->except(['thumbnail', 'gallery'])->toArray();
        $product->update($productData);

        // Rebuild images
        $product->images()->delete();
        $sortOrder = 0;
        if (!empty($validated['thumbnail'])) {
            $product->images()->create([
                'url' => $validated['thumbnail'],
                'is_primary' => true,
                'sort_order' => $sortOrder++,
            ]);
        }
        if (!empty($validated['gallery'])) {
            foreach ($validated['gallery'] as $url) {
                if ($url !== ($validated['thumbnail'] ?? '')) {
                    $product->images()->create([
                        'url' => $url,
                        'is_primary' => false,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }



        return redirect()->route('admin.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Xóa sản phẩm thành công');
    }
}
