<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $type
    ) {
    }

    public function handle(): void
    {
        // Send email based on type
        $email = $this->order->customer_email ?? $this->order->customer->email;

        if (!$email) {
            return;
        }

        // Here you would send the actual email
        // Mail::to($email)->send(new OrderEmail($this->order, $this->type));
    }
}
