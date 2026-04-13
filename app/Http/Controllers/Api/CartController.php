<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function index()
    {
        return response()->json($this->cartService->summary());
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(
            $this->cartService->addItem($data['product_id'], $data['quantity'])
        );
    }

    public function update(Request $request, int $productId)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        return response()->json(
            $this->cartService->updateItem($productId, $data['quantity'])
        );
    }

    public function remove(int $productId)
    {
        return response()->json($this->cartService->removeItem($productId));
    }
}
