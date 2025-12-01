<?php

namespace App\Http\Controllers;

use App\HoldStatus;
use App\Http\Requests\OrderRequest;
use App\Models\Hold;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log ;

class OrderController extends Controller
{
    public function createOrder(OrderRequest $request)
    {
        // Validate and process the order creation logic here
        DB::beginTransaction();
        try {
        $validated = $request->validated();

        $Hold = Hold::with('product')->find($validated['hold_id']);
        $order = Order::create([
            'hold_id' => $validated['hold_id'],
            'status' => 'pre-payment',
            'quantity' => $Hold->quantity,
            'total_amount' => $Hold->product->price * $Hold->quantity,
        ]);
            //we will update the hold status and clear the product cache
            $Hold->update(['status'=>HoldStatus::ACTIVE->value]);
            cache()->forget('products/' . $Hold->product_id);
            
            Log::info("Order created for hold_id: {$validated['hold_id']}");
        DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::alert("Failed to create Order for hold_id: {$validated['hold_id']}", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create order.'], 500);
        }
        return response()->json(['message' => 'Order created successfully',"order_id" => $order->id], 201);
        
    }
}
