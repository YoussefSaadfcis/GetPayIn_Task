<?php

namespace Tests\Feature;

use App\HockStatus;
use App\HoldStatus;
use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WebHockTest extends TestCase
{
    use RefreshDatabase;

    // Test to ensure the same webhook key is processed only once (idempotency)

    public function test_same_webhook_key_is_processed_only_once_idempotency(): void
    {
        $initialStock = 20;
        $holdQuantity = 5;
        $idempotency_key = 'unique-webhook-key-123';
        $product = Product::factory()->create([
            'stock' => $initialStock, // Initial stock: 20
        ]);

        $nonExpiredHold = Hold::factory()->create([
            'product_id' => $product->id,
            'quantity' => $holdQuantity,
            'status' => HoldStatus::ACTIVE->value,
            'expires_at' => now()->addMinute(10), // Expires in the future
        ]);

        $order = Order::factory()->create([
            'hold_id' =>  $nonExpiredHold->id,
            'status' => 'held', // Initial status after hold is placed
            'total_amount' => 100.00,
        ]);

        $product->decrement('stock', $holdQuantity);
        // First webhook processing
        $response1 = $this->postJson('api/webhock', [
            'idempotency_key' => $idempotency_key,
            'order_id' => $order->id,
            'status' => 'completed',
        ]);
        $response1->assertSuccessful()
            ->assertContent(HockStatus::COMPLETED->value);

        $response2 = $this->postJson('api/webhock', [
            'idempotency_key' => $idempotency_key,
            'order_id' => $order->id,
            'status' => 'completed',
        ]);

        $response2->assertSuccessful()
            ->assertContent(HockStatus::DUPLICATE->value);
    }

    public function test_webhook_arrives_before_order_be_processed(): void {
        $initialStock = 20;
        $holdQuantity = 5;
        $idempotency_key = 'unique-webhook-key-123';
        $product = Product::factory()->create([
            'stock' => $initialStock, // Initial stock: 20
        ]);

        $nonExpiredHold = Hold::factory()->create([
            'product_id' => $product->id,
            'quantity' => $holdQuantity,
            'status' => HoldStatus::ACTIVE->value,
            'expires_at' => now()->addMinute(10), // Expires in the future
        ]);
        $response1 = $this->postJson('api/webhock', [
            'idempotency_key' => $idempotency_key,
            'order_id' => 9999, // Non-existent order ID
            'status' => 'completed',
        ]);
        $response1->assertSuccessful()
            ->assertContent(HockStatus::pending_order->value);

    }
}
