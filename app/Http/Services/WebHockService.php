<?php

namespace App\Http\Services;

use App\HockStatus;
use App\HoldStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Webhock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebHockService
{
    protected $returnedStatus = null;

    public function processWebhook($idempotencyKey, $orderId, $webhookStatus)
    {
        // $orderId=5;

        log::info("Processing webhook with key: {$idempotencyKey} for order ID: {$orderId} with status: {$webhookStatus}");
        DB::beginTransaction();
        try {

            $webhook = Webhock::where('idempotency_key', $idempotencyKey)->first();

            if ($webhook) {
                if($webhook->updated_status == HockStatus::COMPLETED->value || $webhook->updated_status == HockStatus::FAILED->value){
                    Log::info("Webhook with key: {$idempotencyKey} has already been processed with status: {$webhook->updated_status}");
                     
                    $this->returnedStatus =  HockStatus::DUPLICATE->value;
                }elseif($webhook->updated_status == HockStatus::pending_order->value){
                    $order = Order::find($orderId);
                    if (!$order) {
                        Log::info("Order not found for webhook with key: {$idempotencyKey}");
                         
                        $this->returnedStatus = HockStatus::pending_order->value;
                    }

                    if ($webhookStatus == HockStatus::COMPLETED->value) {
                        //update order and webhook status
                        $order->update(['status' => 'completed']);
                        $webhook->update(['updated_status' => HockStatus::COMPLETED->value]);
                        Log::info("Order {$orderId} marked as completed for webhook with key: {$idempotencyKey}");
                        $order->hold->update(['status' => HoldStatus::COMPLETED->value]);
                    } elseif ($webhookStatus == HockStatus::FAILED->value) {
                        //update order and webhook status
                        $order->update(['status' => 'failed']);
                        $webhook->update(['updated_status' => HockStatus::FAILED->value]);
                        Log::info("Order {$orderId} marked as failed for webhook with key: {$idempotencyKey}");
                        //release stock if failed and clear cached  product
                        $product = Product::find($order->hold->product_id);
                        if ($product) {
                            $product->increment('stock', $order->hold->quantity);
                            Cache::forget("products/" . $product->id);
                            Log::info("Released stock for product {$product->id} due to failed order {$orderId}");
                            //delete hold
                            $order->hold->delete();
                        }

                         
                        $this->returnedStatus = $order->status;
                    }
                }
            } else {
                $webhook = Webhock::create(
                    [
                        'idempotency_key' => $idempotencyKey,
                        'order_id' => $orderId,
                        'status' => $webhookStatus,
                    ]
                );
                $order = Order::find($orderId);

                // handle out of order webhooks
                if (!$order) {
                    $webhook->update(['updated_status' => HockStatus::pending_order->value]);
                    Log::info("Order not found for webhook with key: {$idempotencyKey}");
                    DB::commit();
                    return  HockStatus::pending_order->value;
                }

                if ($webhookStatus == HockStatus::COMPLETED->value) {
                    //update order and webhook status
                    $order->update(['status' => 'completed']);
                    $webhook->update(['updated_status' => HockStatus::COMPLETED->value]);
                    Log::info("Order {$orderId} marked as completed for webhook with key: {$idempotencyKey}");
                    $order->hold->update(['status' => HoldStatus::COMPLETED->value]);
                    $this->returnedStatus = HoldStatus::COMPLETED->value;
                } elseif ($webhookStatus == HockStatus::FAILED->value) {
                    //update order and webhook status
                    $order->update(['status' => 'failed']);
                    $webhook->update(['updated_status' => HockStatus::FAILED->value]);
                    $this->returnedStatus = HockStatus::FAILED->value;
                    Log::info("Order {$orderId} marked as failed for webhook with key: {$idempotencyKey}");
                    //release stock if failed and clear cached  product
                    $product = Product::find($order->hold->product_id);
                    if ($product) {
                        $product->increment('stock', $order->hold->quantity);
                        Cache::forget("products/" . $product->id);
                        Log::info("Released stock for product {$product->id} due to failed order {$orderId}");
                        //delete hold
                        $order->hold->delete();
                    }

                   
                }
            }
            DB::commit();
            return $this->returnedStatus;
            // return response()->json(['message' => 'Webhook processed successfully'], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == '23000') {
                // Duplicate webhook detected. Log and quietly exit success.
                Log::alert("Duplicate webhook received for key: {$idempotencyKey}");
                Log::alert("Error processing webhook: " . $e->getMessage());
                return HockStatus::DUPLICATE->value;
                // return response()->json(['message' => 'Webhook already processed.'], 200);
            }
            Log::alert("Error processing webhook: " . $e->getMessage());
        };
    }
}
