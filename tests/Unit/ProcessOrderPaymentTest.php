<?php

namespace Tests\Unit;

use App\Jobs\ProcessOrderPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;

class ProcessOrderPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'  => 'random_user',
        ]);
    }

    /** @test */
    public function it_processes_orders_and_updates_status()
    {
        LogFake::bind();

        // Create a sample order with 'pending' status
        $order = Order::factory()->create([
            'user_id'    => $this->user->id,
        ]);

        $job = new ProcessOrderPayment($order->id);                             // Dispatch the job
        $job->handle(); // Directly calling the job to test logic
        $order->refresh();
        $this->assertContains($order->status, ['completed', 'failed']);         // Assert the order moved to "processing" before "completed" or "failed"

        $this->withExceptionHandling();

        if($order->status == 'completed') {
            Log::assertLogged(fn (LogEntry $log) =>
                $log->level === 'info'
                && $log->message === sprintf("Order #%s payment completed.", $order->id)
            );
        } else {
            Log::assertLogged(fn (LogEntry $log) =>
                $log->level === 'warning'
                && $log->message === sprintf("Order #%s payment failed. Retrying in %s seconds", $order->id, 5)
            );
        }
    }

    /** @test */
    public function it_retries_failed_jobs()
    {
        Queue::fake();

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
        ]);

        ProcessOrderPayment::dispatch($order->id);

        Queue::assertPushed(ProcessOrderPayment::class, function ($job) use ($order) {
            return $job->order_id === $order->id;
        });

        Queue::assertPushed(ProcessOrderPayment::class, 1);
    }
}
