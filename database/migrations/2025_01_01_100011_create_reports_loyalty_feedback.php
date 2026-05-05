<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_sales_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->date('date');
            $table->integer('total_orders')->default(0);
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('total_discounts', 12, 2)->default(0);
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('cash_sales', 12, 2)->default(0);
            $table->decimal('card_sales', 12, 2)->default(0);
            $table->decimal('upi_sales', 12, 2)->default(0);
            $table->decimal('online_sales', 12, 2)->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->integer('average_table_turnaround')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->timestamps();
            $table->unique(['restaurant_id', 'date']);
        });

        Schema::create('product_sales_report', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->foreignId('product_id')->constrained('products');
            $table->date('date');
            $table->integer('quantity_sold')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('total_discount', 12, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['restaurant_id', 'product_id', 'date']);
        });

        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('points_per_rupee', 5, 2)->default(1);
            $table->integer('minimum_points_for_redemption')->default(100);
            $table->decimal('point_value_in_rupees', 5, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->integer('points_earned')->default(0);
            $table->integer('points_redeemed')->default(0);
            $table->enum('transaction_type', ['earned', 'redeemed', 'expired', 'bonus', 'adjustment']);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->integer('rating')->default(5);
            $table->integer('food_rating')->nullable();
            $table->integer('service_rating')->nullable();
            $table->integer('ambience_rating')->nullable();
            $table->integer('value_for_money_rating')->nullable();
            $table->text('comments')->nullable();
            $table->boolean('is_published')->default(false);
            $table->text('staff_response')->nullable();
            $table->unsignedBigInteger('responded_by')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_programs');
        Schema::dropIfExists('product_sales_report');
        Schema::dropIfExists('daily_sales_summary');
    }
};
