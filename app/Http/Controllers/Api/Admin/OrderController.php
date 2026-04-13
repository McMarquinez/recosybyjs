<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        return response()->json(
            Order::with('items')->latest()->get()
        );
    }

    public function updateStatus(Request $request, int $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending_payment,processing,paid,shipped,completed,cancelled'],
            'payment_status' => ['nullable', 'string', 'in:unpaid,proof_uploaded,paid,rejected'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_proof' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('payment_proof')) {
            $data['payment_proof_path'] = $request->file('payment_proof')->store('payment-proofs', 'public');
        }

        if (($data['payment_status'] ?? null) === 'paid') {
            $data['paid_at'] = now();
        }

        $order->update($data);

        return response()->json($order->fresh()->load('items'));
    }
}
