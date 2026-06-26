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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // ربط السجل بجدول المنتجات 
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // تفاصيل المنتج داخل هذا الأوردر تحديداً
            $table->integer('quantity'); // الكمية المطلوبة من هذا المنتج في هذا الأوردر
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
