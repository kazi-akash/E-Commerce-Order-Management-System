<?php

namespace App\Jobs;

use App\Repositories\ProductRepository;
use App\Events\LowStockDetected;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ProductRepository $productRepository): void
    {
        $lowStockProducts = $productRepository->getLowStockProducts();

        foreach ($lowStockProducts as $product) {
            if ($product->inventory) {
                event(new LowStockDetected(
                    $product,
                    $product->inventory->available_quantity,
                    $product->low_stock_threshold
                ));
            }
        }
    }
}
