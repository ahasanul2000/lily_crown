<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Blog::latest()->get()
        ]);
    }

    public function show($slug)
    {
        $blog = Blog::where('slug', $slug)->first();
        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }
        return response()->json(['data' => $blog]);
    }
}
