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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->unique(); // e.g., PAY-XXXX
            $table->foreignId('order_id')->constrained('orders')->onDelete('restrict');
            $table->enum('status', ['pending', 'successful', 'failed'])->default('pending');
            $table->enum('payment_method', ['credit_card', 'paypal', 'stripe', 'cash'])->default('credit_card');
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable(); // Gateway transaction ID
            $table->json('gateway_response')->nullable(); // Raw gateway response
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
