<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EcommerceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_products_can_be_listed_and_viewed_by_slug(): void
    {
        $activeProduct = Product::create([
            'name' => 'Wireless Mouse',
            'slug' => 'wireless-mouse',
            'description' => 'Ergonomic mouse',
            'price' => 799.99,
            'stock' => 10,
            'status' => 'active',
        ]);

        Product::create([
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'description' => 'Not for sale',
            'price' => 299.99,
            'stock' => 5,
            'status' => 'draft',
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $activeProduct->id,
                'slug' => 'wireless-mouse',
            ]);

        $this->getJson('/api/products/wireless-mouse')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $activeProduct->id,
                'name' => 'Wireless Mouse',
            ]);
    }

    public function test_admin_can_create_a_product_with_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/api/admin/products', [
            'name' => 'Gaming Keyboard',
            'description' => 'Mechanical keyboard',
            'price' => 2499.50,
            'stock' => 15,
            'status' => 'active',
            'images' => [
                UploadedFile::fake()->image('keyboard-1.jpg'),
                UploadedFile::fake()->image('keyboard-2.jpg'),
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('slug', 'gaming-keyboard')
            ->assertJsonCount(2, 'images');

        $this->assertDatabaseHas('products', [
            'name' => 'Gaming Keyboard',
            'slug' => 'gaming-keyboard',
        ]);

        $this->assertDatabaseCount('product_images', 2);
    }

    public function test_guest_can_add_to_cart_and_checkout_with_payment_proof(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Bluetooth Speaker',
            'slug' => 'bluetooth-speaker',
            'description' => 'Portable speaker',
            'price' => 1500.00,
            'stock' => 7,
            'status' => 'active',
        ]);

        $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk()
            ->assertJsonPath('total', 3000);

        $response = $this->post('/api/checkout', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'customer_phone' => '+639171234567',
            'address' => '123 Sample Street, Manila',
            'payment_reference' => 'GCASH-REF-1001',
            'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending_payment')
            ->assertJsonPath('payment_method', 'gcash_manual')
            ->assertJsonPath('payment_status', 'proof_uploaded')
            ->assertJsonCount(1, 'items');

        $order = Order::first();

        $this->assertNotNull($order);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_email' => 'jane@example.com',
            'customer_phone' => '+639171234567',
            'total' => 3000,
            'payment_reference' => 'GCASH-REF-1001',
            'payment_status' => 'proof_uploaded',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'line_total' => 3000,
        ]);

        $this->assertSame(5, $product->fresh()->stock);
        Storage::disk('public')->assertExists($order->payment_proof_path);
    }

    public function test_admin_can_update_order_status_and_payment_status(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::create([
            'order_number' => 'ORD-20260412010101-ABCD',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '+639171231111',
            'customer_address' => '456 Test Street',
            'subtotal' => 2000,
            'total' => 2000,
            'status' => 'pending_payment',
            'payment_method' => 'gcash_manual',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->put('/api/admin/orders/'.$order->id.'/status', [
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_reference' => 'GCASH-PAID-777',
            'payment_proof' => UploadedFile::fake()->image('paid-proof.jpg'),
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'processing')
            ->assertJsonPath('payment_status', 'paid')
            ->assertJsonPath('payment_reference', 'GCASH-PAID-777');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_reference' => 'GCASH-PAID-777',
        ]);

        $this->assertNotNull($order->fresh()->paid_at);
        Storage::disk('public')->assertExists($order->fresh()->payment_proof_path);
    }
}
