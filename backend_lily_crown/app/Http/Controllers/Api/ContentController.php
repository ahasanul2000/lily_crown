<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\HeroSlide;
use App\Models\InstagramPost;
use App\Models\Lookbook;
use App\Models\Testimonial;
use Illuminate\Http\Request;

/** Homepage content managed from the admin panel. */
class ContentController extends Controller
{
    public function heroSlides()
    {
        return response()->json(['data' => HeroSlide::visible()->get()]);
    }

    public function testimonials()
    {
        return response()->json(['data' => Testimonial::visible()->get()]);
    }

    public function features()
    {
        return response()->json(['data' => Feature::visible()->get()]);
    }

    public function instagramPosts()
    {
        return response()->json(['data' => InstagramPost::visible()->limit(12)->get()]);
    }

    public function lookbook(Request $request)
    {
        $perPage = min(48, max(1, (int) $request->query('per_page', 8)));
        $page = Lookbook::visible()->with('product.category')->paginate($perPage);

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
}
