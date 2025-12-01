<?php

namespace Tests\Feature;

use App\HoldStatus;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiredHoldCleanupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that running the cleanup command returns the quantity of expired
     * holds back to the product stock and deletes the hold record.
     */
    public function test_expired_hold_returns_stock_and_is_deleted(): void
    {
        // 1. Setup: Create Product and initial state
        $initialStock = 20;
        $holdQuantity = 5;

        $product = Product::factory()->create([
            'stock' => $initialStock, // Initial stock: 20
        ]);

        // 2. Create the Hold and decrement stock (simulating a successful hold creation)
        $this->travel(1)->minute(); // Move time forward slightly to differentiate creation

        // Simulating the HoldController logic: stock should be 15 after this.
        $product->decrement('stock', $holdQuantity);

        $expiredHold = Hold::factory()->create([
            'product_id' => $product->id,
            'quantity' => $holdQuantity, // Hold quantity: 5
            'status' => HoldStatus::EXPIRED->value, // Assume it was initially expired

            // Set expiration in the past (1 minute ago)
            'expires_at' => now()->subMinute(),
        ]);

        // Verification check before command runs
        $this->assertEquals($initialStock - $holdQuantity, $product->fresh()->stock, 'Stock should be reduced before cleanup.');
        $this->assertDatabaseHas('holds', ['id' => $expiredHold->id]);


        // 3. Execution: Run the Artisan command
        $this->artisan('app:release-expired-holds')
            ->assertSuccessful();

        // 4. Assert Final State

        // The Hold record must be deleted
        $this->assertDatabaseMissing('holds', ['id' => $expiredHold->id]);

        // The stock must be fully returned (20 - 5 + 5 = 20)
        $this->assertEquals($initialStock, $product->fresh()->stock, 'Stock should be fully restored after hold expiration.');

        // 5. Test that a non-expired hold is NOT affected
        $nonExpiredHold = Hold::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'status' => HoldStatus::ACTIVE->value,
            'expires_at' => now()->addMinute(10), // Expires in the future
        ]);

        $product->decrement('stock', 2);


        // Run the command again
        $this->artisan('app:release-expired-holds')
            ->assertSuccessful();

        // The non-expired hold must still exist
        $this->assertDatabaseHas('holds', ['id' => $nonExpiredHold->id]);

        // Stock should only be reduced by the non-expired hold (20 - 2 = 18)
        $this->assertEquals(($initialStock - 2), $product->fresh()->stock, 'Stock should not include the quantity of non-expired holds.');
    }
}
