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
        Schema::create('debt_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('debt_item_id')->constrained()->cascadeOnDelete();
            $table->double('amount');
            $table->double('remaining_debt');
            $table->foreignId('store_id')->nullable()->constrained('stores')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_payment_items');
    }
};
