<?php

namespace App\Http\Controllers;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HoldController extends Controller
{
    public function create($product_id, $qty)
    {
        DB::beginTransaction();
        try {
            $product = Product::where('id', $product_id)
                ->where('stock', '>=', $qty)
                ->decrement('stock', $qty);

            if ($product === 0) {
                DB::rollBack();
                return response()->json(['error' => 'Insufficient product quantity.'], 400);
            }

            $expires_at = now()->addMinutes(2);
            $Hold = Hold::create([
                'product_id' => $product_id,
                'expires_at' => $expires_at,
                'status' => 'active',
                'quantity' => $qty,
            ]);


            DB::commit();
            // we will clear the cache for this product because we used decrement above (won't trigger Product observer)
            cache()->forget('products/' . $product_id);

            return response()->json([$Hold->id, $expires_at], 201);
        } catch (\Exception $e) {

            Log::alert("Failed to make Hold for product_id: $product_id", ['error' => $e->getMessage()]);
            DB::rollBack();

            return response()->json(['error' => 'Failed to create hold.'], 500);
        }
    }
}
