<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class CartService
{
    private const SESSION_KEY = 'cart.items';

    public function all(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function addItem(int $productId, int $quantity): array
    {
        $product = Product::query()
            ->whereKey($productId)
            ->where('status', 'active')
            ->firstOrFail();

        return $this->add($product, $quantity);
    }

    public function add(Product $product, int $quantity): array
    {
        $items = $this->all();
        $existingQuantity = data_get($items, "{$product->id}.quantity", 0);
        $newQuantity = $existingQuantity + $quantity;

        if ($newQuantity > $product->stock) {
            abort(422, 'Requested quantity exceeds available stock.');
        }

        $items[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'quantity' => $newQuantity,
            'line_total' => round($newQuantity * (float) $product->price, 2),
        ];

        session([self::SESSION_KEY => $items]);

        return $this->summary();
    }

    public function updateItem(int $productId, int $quantity): array
    {
        $product = Product::findOrFail($productId);

        return $this->update($product, $quantity);
    }

    public function update(Product $product, int $quantity): array
    {
        $items = $this->all();

        if (! isset($items[$product->id])) {
            abort(404, 'Cart item not found.');
        }

        if ($quantity <= 0) {
            unset($items[$product->id]);
        } else {
            if ($quantity > $product->stock) {
                abort(422, 'Requested quantity exceeds available stock.');
            }

            $items[$product->id]['quantity'] = $quantity;
            $items[$product->id]['line_total'] = round($quantity * (float) $product->price, 2);
        }

        session([self::SESSION_KEY => $items]);

        return $this->summary();
    }

    public function removeItem(int $productId): array
    {
        return $this->remove($productId);
    }

    public function remove(int $productId): array
    {
        $items = $this->all();
        unset($items[$productId]);

        session([self::SESSION_KEY => $items]);

        return $this->summary();
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function summary(): array
    {
        $items = array_values($this->all());
        $total = Collection::make($items)->sum('line_total');

        return [
            'items' => $items,
            'total' => round($total, 2),
        ];
    }
}
