<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Jobs\SendOrderEmailJob;

class SendOrderConfirmationEmail
{
    public function handle(OrderConfirmed $event): void
    {
        SendOrderEmailJob::dispatch($event->order, 'confirmed');
    }
}
