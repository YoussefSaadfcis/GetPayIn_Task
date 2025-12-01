<?php

namespace App\Console\Commands;

use App\HockStatus;
use App\Http\Services\WebHockService;
use App\Models\Webhock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPendingWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-pending-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $webhooks = Webhock::where('updated_status', HockStatus::pending_order->value)->get();
            foreach ($webhooks as $webhook) {
                $WebHockService = new WebHockService();
                $WebHockService->processWebhook($webhook->idempotency_key, $webhook->order_id, $webhook->status);
                Log::alert("$webhook->order_id");
            }
        } catch (\Exception $e) {
            Log::alert("Error processing pending webhooks: " . $e->getMessage());
        }
    }
}
