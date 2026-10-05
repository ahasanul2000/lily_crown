<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Filters: category (slug), search, min_price, max_price, featured, trending, in_stock, sort.
     * Paginates when per_page is given; otherwise returns every matching product.
     */
    public function index(Request $request)
    {
        $query = Product::active()
            ->with('category')
            ->whereHas('category', fn ($q) => $q->where('is_active', true));

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter on the effective price (sale price when one is set).
        $effectivePrice = 'CASE WHEN sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price THEN sale_price ELSE price END';
        if (is_numeric($request->query('min_price'))) {
            $query->whereRaw("$effectivePrice >= CAST(? AS REAL)", [(float) $request->query('min_price')]);
        }
        if (is_numeric($request->query('max_price'))) {
            $query->whereRaw("$effectivePrice <= CAST(? AS REAL)", [(float) $request->query('max_price')]);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }
        if ($request->boolean('trending')) {
            $query->where('is_trending', true);
        }
        if ($request->boolean('on_sale')) {
            $query->whereNotNull('sale_price')->where('sale_price', '>', 0)->whereColumn('sale_price', '<', 'price');
        }

        match ($request->query('sort')) {
            'price_asc' => $query->orderByRaw("$effectivePrice asc"),
            'price_desc' => $query->orderByRaw("$effectivePrice desc"),
            'newest' => $query->latest('id'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order')->latest('id'),
        };

        if ($request->filled('per_page')) {
            $perPage = min(60, max(1, (int) $request->query('per_page')));
            $page = $query->paginate($perPage)->withQueryString();

            return response()->json([
                'data' => $page->items(),
                'meta' => [
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total(),
                ],
            ]);
        }

        return response()->json(['data' => $query->get()]);
    }

    /** Accepts either a numeric id or a slug. */
    public function show(string $idOrSlug)
    {
        $product = Product::active()
            ->with('category')
            ->where(is_numeric($idOrSlug) ? 'id' : 'slug', $idOrSlug)
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json(['data' => $product]);
    }

    public function related(string $id)
    {
        $product = Product::find($id);
        if (! $product) {
            return response()->json(['data' => []]);
        }

        $related = Product::active()
            ->with('category')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        if ($related->count() < 4) {
            $related = $related->concat(
                Product::active()->with('category')
                    ->whereNotIn('id', $related->pluck('id')->push($product->id))
                    ->inRandomOrder()
                    ->limit(4 - $related->count())
                    ->get()
            );
        }

        return response()->json(['data' => $related->values()]);
    }

    /** Lightweight lookup for wishlist / recently-viewed lists. */
    public function byIds(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->filter(fn ($id) => ctype_digit(trim($id)))
            ->map(fn ($id) => (int) $id)
            ->take(100);

        return response()->json([
            'data' => Product::active()->with('category')->whereIn('id', $ids)->get(),
        ]);
    }
}
