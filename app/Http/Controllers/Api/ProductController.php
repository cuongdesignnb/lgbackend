<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get all products with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'brand', 'images'])
            ->where('is_active', true);

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Filter by category (including children of parent categories)
        if ($request->category) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $categoryIds = [$category->id];
                // If it's a parent category, include all children
                $childIds = Category::where('parent_id', $category->id)->pluck('id')->toArray();
                $categoryIds = array_merge($categoryIds, $childIds);
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // Filter by brand
        if ($request->brand) {
            $query->whereHas('brand', function ($q) use ($request) {
                $q->where('slug', $request->brand);
            });
        }



        // Price range
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        // Featured only
        if ($request->featured) {
            $query->where('is_featured', true);
        }

        // Sorting
        $sortField = $request->sort ?? 'created_at';
        $sortDirection = $request->order ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $products = $query->paginate($request->per_page ?? 20);

        return response()->json($products);
    }

    /**
     * Get featured products with admin-configurable fallback chain:
     *   1. Explicit `homepage_featured_product_ids` setting (CSV of IDs).
     *   2. Products flagged is_featured.
     *   3. Latest products in `homepage_featured_category_ids`/_slugs (if set).
     *   4. Latest active products overall.
     */
    public function featured(): JsonResponse
    {
        $base = Product::with(['category', 'brand', 'images'])->where('is_active', true);

        // 1) Explicit pinned IDs
        $explicitIds = array_filter(array_map('trim', explode(',', (string) \App\Models\Setting::get('homepage_featured_product_ids', ''))));
        if (!empty($explicitIds)) {
            $products = (clone $base)->whereIn('id', $explicitIds)->limit(12)->get()
                ->sortBy(fn($p) => array_search((string) $p->id, $explicitIds))
                ->values();
            if ($products->isNotEmpty()) return response()->json($products);
        }

        // 2) is_featured flag
        $products = (clone $base)->where('is_featured', true)->latest()->limit(8)->get();
        if ($products->isNotEmpty()) return response()->json($products);

        // 3) Featured categories (by ID or slug)
        $catIds = array_filter(array_map('trim', explode(',', (string) \App\Models\Setting::get('homepage_featured_category_ids', ''))));
        $catSlugs = array_filter(array_map('trim', explode(',', (string) \App\Models\Setting::get('homepage_featured_category_slugs', ''))));
        if (!empty($catSlugs)) {
            $catIds = array_merge(
                $catIds,
                Category::whereIn('slug', $catSlugs)->pluck('id')->all()
            );
        }
        if (!empty($catIds)) {
            $products = (clone $base)->whereIn('category_id', $catIds)->latest()->limit(8)->get();
            if ($products->isNotEmpty()) return response()->json($products);
        }

        // 4) Latest active products overall
        $products = (clone $base)->latest()->limit(8)->get();
        return response()->json($products);
    }

    /**
     * Get single product by slug
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::with([
            'category',
            'brand',

            'images',
            'reviews' => fn ($query) => $query
                ->where('is_approved', true)
                ->latest()
                ->with(['user:id,name']),
        ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Append parsed specifications
        $product->append('parsed_specifications');

        // Get related products
        $related = Product::with(['images'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->limit(4)
            ->get();

        return response()->json([
            'product' => $product,
            'related' => $related,
        ]);
    }

}
