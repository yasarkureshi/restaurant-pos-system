<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('payment_number', 50)->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'card', 'upi', 'wallet', 'credit', 'debit', 'net_banking', 'food_coupon', 'loyalty_points', 'zomato_pay', 'swiggy_money', 'other']);
            $table->string('card_type', 50)->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_bank', 100)->nullable();
            $table->string('upi_id', 100)->nullable();
            $table->string('upi_transaction_id', 100)->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('payment_gateway', 50)->nullable();
            $table->json('gateway_response')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'partial_refund'])->default('completed');
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->text('refund_reason')->nullable();
            $table->decimal('cash_tendered', 10, 2)->nullable();
            $table->decimal('cash_change', 10, 2)->nullable();
            $table->foreignId('processed_by')->constrained('users');
            $table->timestamp('payment_date')->useCurrent();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->string('invoice_number', 50)->unique();
            $table->enum('invoice_type', ['tax_invoice', 'bill_of_supply', 'proforma'])->default('tax_invoice');
            $table->string('customer_name')->nullable();
            $table->string('customer_gstin', 50)->nullable();
            $table->text('customer_address')->nullable();
            $table->decimal('sub_total', 10, 2);
            $table->decimal('cgst_amount', 10, 2)->default(0);
            $table->decimal('sgst_amount', 10, 2)->default(0);
            $table->decimal('igst_amount', 10, 2)->default(0);
            $table->decimal('cess_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('round_off', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->text('amount_in_words')->nullable();
            $table->enum('status', ['active', 'cancelled', 'modified'])->default('active');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->boolean('printed')->default(false);
            $table->timestamp('printed_at')->nullable();
            $table->integer('print_count')->default(0);
            $table->string('irn', 100)->nullable();
            $table->text('qr_code')->nullable();
            $table->string('ack_no', 100)->nullable();
            $table->timestamp('ack_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payments');
    }
};
