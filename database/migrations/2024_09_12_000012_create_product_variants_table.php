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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('quantity');
            $table->double('price');
            $table->integer('stock')->default(0);
            $table->enum('stock_status', ['in-stock', 'limit-stock', 'out-of-stock', 'not-set'])->default('not-set');
            $table->enum('status', ['draft', 'active', 'inactive'])->default('active');
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
        Schema::dropIfExists('product_variants');
    }
};
