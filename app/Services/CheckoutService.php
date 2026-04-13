<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(private CartService $cartService)
    {
    }

    public function checkout(array $customerData, ?UploadedFile $paymentProof = null): Order
    {
        $cart = $this->cartService->summary();

        if (empty($cart['items'])) {
            abort(422, 'Cart is empty.');
        }

        return DB::transaction(function () use ($cart, $customerData, $paymentProof) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $customerData['name'],
                'customer_email' => $customerData['email'],
                'customer_phone' => $customerData['customer_phone'],
                'customer_address' => $customerData['address'],
                'subtotal' => $cart['total'],
                'total' => $cart['total'],
                'status' => 'pending_payment',
                'payment_method' => 'gcash_manual',
                'payment_status' => $paymentProof ? 'proof_uploaded' : 'unpaid',
                'payment_reference' => $customerData['payment_reference'] ?? null,
                'payment_proof_path' => $paymentProof?->store('payment-proofs', 'public'),
            ]);

            foreach ($cart['items'] as $item) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($item['quantity'] > $product->stock) {
                    abort(422, "Insufficient stock for {$product->name}.");
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);

                $product->decrement('stock', $item['quantity']);
            }

            $this->cartService->clear();

            return $order->load('items');
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
