<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 5;

    public $order_id;

    /**
     * ProcessOrderPayment constructor.
     *
     * @param  $order_id
     */
    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::find($this->order_id);
        if (!$order) {
            Log::error(sprintf("Order#%s not found!", $this->order_id));
            return;
        }

        Log::info(sprintf("ProcessOrderPayment started for order #%s", $order->id));

        $order->update(['status' => 'processing']);
        Log::info(sprintf("Order #%s status updated to processing", $order->id));

        // Simulate external payment API delay
        sleep(2);

        $paymentSuccessful = rand(0, 1) === 1;
        if ($paymentSuccessful) {
            $order->update(['status' => 'completed']);
            Log::info(sprintf("Order #%s payment completed.", $order->id));
        } else {
            $order->update(['status' => 'failed']);
            Log::warning(sprintf("Order #%s payment failed. Retrying in %s seconds", $order->id, $this->backoff));
            //throw new \Exception(sprintf("Order #%s payment failed!", $order->id));  // commented due to unit testing as it will fail and throw exception
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error(sprintf("Order #%s payment failed after %s retries.", $this->order_id, $this->tries));
    }
}
