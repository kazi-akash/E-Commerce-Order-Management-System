<?php

namespace App\Console\Commands;

use App\Jobs\CheckLowStockJob;
use Illuminate\Console\Command;

class CheckLowStockCommand extends Command
{
    protected $signature = 'stock:check-low';
    protected $description = 'Check for low stock products and create alerts';

    public function handle(): int
    {
        $this->info('Checking for low stock products...');

        CheckLowStockJob::dispatch();

        $this->info('Low stock check job dispatched successfully.');

        return 0;
    }
}
