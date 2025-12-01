<?php

namespace App\Http\Controllers;


use App\Http\Requests\WebHockRequest;
use App\Http\Services\WebHockService;


class WebHockController extends Controller
{
    protected  WebHockService $webHockService;
    public function __construct(protected WebHockService $HockService)
    {
        $this->webHockService = $HockService;
    }
    public function handle(WebHockRequest $request)
    {
        $validated = $request->validated();
        $idempotencyKey = $validated['idempotency_key'];
        $webhookStatus =  $validated['status'];
        $orderId =  $validated['order_id'];

        return $this->webHockService->processWebhook($idempotencyKey, $orderId, $webhookStatus);
    }
}
