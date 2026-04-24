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
     * Get featured products
     */
    public function featured(): JsonResponse
    {
        $products = Product::with(['category', 'brand', 'images'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->limit(8)
            ->get();

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
