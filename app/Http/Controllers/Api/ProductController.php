<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(
            Product::with('images')
                ->where('status', 'active')
                ->latest()
                ->get()
        );
    }

    public function show(string $slug)
    {
        $product = Product::with('images')
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        return response()->json($product);
    }
}
