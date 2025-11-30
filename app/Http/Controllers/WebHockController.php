<?php

namespace App\Http\Controllers;

use App\HockStatus;
use App\Http\Requests\WebHockRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Webhock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebHockController extends Controller
{
    public function handle(WebHockRequest $request)
    {
        $validated = $request->validated();
        $idempotencyKey = $validated['idempotency_key'];
        $webhookStatus =  $validated['status'];
        $orderId =  $validated['order_id'];

        DB::beginTransaction();
        try {
            $webhook = Webhock::Create(
                [
                    'idempotency_key' => $idempotencyKey,
                    'order_id' => $orderId,
                    'status' => $webhookStatus
                ]
            );

            $order = Order::find($orderId);
            // handle out of order webhooks
            if (!$order) {
                $webhook->update(['status' => HockStatus::pending_order->value]);
                Log::info("Order not found for webhook with key: {$idempotencyKey}");
                DB::commit();
                return response()->json(['message' => 'Order not found.'], 404);
            } else {

                if ($webhookStatus == HockStatus::COMPLETED->value) {
                    //update order and webhook status
                    $order->update(['status' => 'completed']);
                    $webhook->update(['status' => HockStatus::COMPLETED->value]);
                    Log::info("Order {$orderId} marked as completed for webhook with key: {$idempotencyKey}");
                } elseif ($webhookStatus == HockStatus::FAILED->value) {
                    //update order and webhook status
                    $order->update(['status' => 'failed']);
                    $webhook->update(['status' => HockStatus::FAILED->value]);
                    Log::info("Order {$orderId} marked as failed for webhook with key: {$idempotencyKey}");
                    //release stock if failed and clear cached  product
                    $product = Product::find($order->hold->product_id);
                    if ($product) {
                        $product->increment('stock', $order->hold->quantity);
                        Cache::forget("products/" . $product->id);
                        Log::info("Released stock for product {$product->id} due to failed order {$orderId}");
                    }
                    //delete hold
                    $order->hold->delete();
                    DB::commit();
                    return response()->json(['message' => 'Order failed, stock released and hold deleted.'], 200);
                }
            }


            DB::commit();
            return response()->json(['message' => 'Webhook processed successfully'], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == '23000') {
                // Duplicate webhook detected. Log and quietly exit success.
                Log::info("Duplicate webhook received for key: {$idempotencyKey}. Ignoring.");
                return response()->json(['message' => 'Webhook already processed.'], 200);
            }
            Log::info("Error processing webhook: " . $e->getMessage());
        };
    }
}
