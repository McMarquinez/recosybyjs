<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(
            Product::with('images')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:draft,active,inactive'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $product = Product::create([
            'name' => $data['name'],
            'category' => filled($data['category'] ?? null) ? $data['category'] : null,
            'slug' => $this->resolveSlug(
                $data['slug'] ?? $data['name']
            ),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'stock' => $data['stock'],
            'status' => $data['status'],
        ]);

        foreach ($request->file('images', []) as $index => $image) {
            $product->images()->create([
                'image_path' => $image->store('products', 'public'),
                'is_primary' => $index === 0,
            ]);
        }

        return response()->json($product->load('images'), 201);
    }

    public function update(Request $request, int $id)
    {
        $product = Product::with('images')->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,' . $product->id],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', 'string', 'in:draft,active,inactive'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
        ]);

        if (array_key_exists('slug', $data) && blank($data['slug']) && isset($data['name'])) {
            $data['slug'] = $this->resolveSlug($data['name'], $product->id);
        } elseif (filled($data['slug'] ?? null)) {
            $data['slug'] = $this->resolveSlug($data['slug'], $product->id);
        } elseif (isset($data['name']) && blank($product->slug)) {
            $data['slug'] = $this->resolveSlug($data['name'], $product->id);
        }

        if (array_key_exists('category', $data) && blank($data['category'])) {
            $data['category'] = null;
        }

        $product->update($data);

        if ($request->hasFile('images')) {
            $product->images()->delete();

            foreach ($request->file('images', []) as $index => $image) {
                $product->images()->create([
                    'image_path' => $image->store('products', 'public'),
                    'is_primary' => $index === 0,
                ]);
            }
        }

        return response()->json($product->fresh()->load('images'));
    }

    public function destroy(int $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    private function resolveSlug(string $value, ?int $ignoreProductId = null): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Product::query()
                ->when($ignoreProductId, fn ($query) => $query->whereKeyNot($ignoreProductId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
