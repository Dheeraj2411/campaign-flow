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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('gateway'); // razorpay | stripe
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_order_id')->nullable();
            $table->string('plan'); // pro | enterprise
            $table->integer('amount'); // in smallest currency unit (paise/cents)
            $table->string('currency', 10)->default('INR');
            $table->string('status')->default('pending'); // pending | completed | failed
            $table->jsonb('receipt_data')->nullable();
            $table->timestampTz('activated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
