<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->morphs('inventoriable'); // product_id or product_variant_id (already creates index)
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0); // For pending orders
            $table->integer('available_quantity')->storedAs('quantity - reserved_quantity');
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
