<?php

namespace Tests\Feature;

use App\HoldStatus;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StockHoldConcurrencyTest extends TestCase
{
    use RefreshDatabase;


    public function test_concurrent_holds_at_stock_boundary_prevent_oversell(): void
    {
        // 1. Setup: Create a product with a fixed stock boundary
        $initialStock = 10;
        $holdQuantity = 10;

        $product = Product::factory()->create([
            'stock' => $initialStock,
        ]);

        $data = [
            'product_id' => $product->id,
            'qty' => $holdQuantity,
        ];
// Log::info($product->toArray());
        // 2. First Attempt (Should Succeed)
        // In a synchronous test, this request fully completes before the next one starts.
        // It should successfully take the stock from 10 to 0 and create the Hold record.
        $response1 = $this->postJson('api/hold', $data);

        $response1->assertStatus(201)
            ->assertJsonCount(2); // Should return [hold_id, expires_at]

        // 3. Second Attempt (Should Fail)
        // This request immediately runs into the concurrency guard:
        // $product->where('stock', '>=', 10)->decrement('stock', 10);
        // Since the stock is now 0, the WHERE clause fails, $product returns 0 rows updated,
        // triggering the rollback and the 400 error in the controller.
        $response2 = $this->postJson('api/hold', $data);

        $response2->assertStatus(400)
            ->assertJson(['error' => 'Insufficient product quantity.']);

        // 4. Assert Final State
        // Ensure only one Hold record was created
        $this->assertCount(1, Hold::all());

        // Ensure the single Hold record used the correct product and quantity
        $this->assertDatabaseHas('holds', [
            'product_id' => $product->id,
            'quantity' => $holdQuantity,
            'status' => HoldStatus::EXPIRED->value,
        ]);

        // Ensure the final stock is 0 (all stock was taken by the first request)
        $this->assertEquals(0, $product->fresh()->stock);

        // 5. Assert database state for the failed request
        // Since the second request failed, no further Holds should exist.
        $this->assertDatabaseMissing('holds', [
            // Check for any unintended hold creations with the same details
            'id' => Hold::first()->id + 1,
            'product_id' => $product->id,
        ]);
    }
}
