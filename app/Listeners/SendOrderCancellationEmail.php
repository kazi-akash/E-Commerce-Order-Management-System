<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Jobs\SendOrderEmailJob;

class SendOrderCancellationEmail
{
    public function handle(OrderCancelled $event): void
    {
        SendOrderEmailJob::dispatch($event->order, 'cancelled');
    }
}
