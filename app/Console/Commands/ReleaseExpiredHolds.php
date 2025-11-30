<?php

namespace App\Console\Commands;

use App\HoldStatus;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:release-expired-holds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release expired holds on products and restore stock quantity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::alert("Starting release of expired holds.");

        $expiredHolds = Hold::with('product')->where('expires_at', '<=', now())
            ->where('status', HoldStatus::EXPIRED->value)
            ->get();
        foreach ($expiredHolds as $hold) {
            DB::beginTransaction();
            try {
                // Log::alert("Releasing hold ID: {$hold->id} for product ID: {$hold->product_id}, quantity: {$hold->quantity}");

                if ($hold->product) {
                    Product::where('id', $hold->product->id)->increment('stock', $hold->quantity);
                }

                $hold->delete();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::alert("Failed to release hold ID: {$hold->id}. Error: ",  ['error' => $e->getMessage()]);
            }
        }
    }
}
