<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('order_number', 50)->unique();
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery', 'online', 'catering', 'room_service'])->default('dine_in');
            $table->unsignedBigInteger('table_id')->nullable();
            $table->unsignedBigInteger('waiter_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->unsignedBigInteger('delivery_address_id')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('delivery_partner', 100)->nullable();
            $table->decimal('delivery_charges', 10, 2)->default(0);
            $table->integer('estimated_delivery_time')->nullable();
            $table->integer('number_of_guests')->default(1);
            $table->enum('status', ['draft', 'placed', 'confirmed', 'preparing', 'ready', 'served', 'completed', 'cancelled', 'refunded', 'failed'])->default('draft');
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'refunded', 'failed'])->default('pending');
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->enum('discount_type', ['percentage', 'fixed', 'coupon', 'manual', 'loyalty'])->nullable();
            $table->text('discount_reason')->nullable();
            $table->decimal('service_charge_amount', 10, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('packaging_charge', 10, 2)->default(0);
            $table->decimal('rounding_adjustment', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_amount', 10, 2)->default(0);
            $table->timestamp('order_datetime')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('kot_number', 50)->nullable();
            $table->boolean('kot_printed')->default(false);
            $table->timestamp('kot_printed_at')->nullable();
            $table->text('special_instructions')->nullable();
            $table->json('order_tags')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->enum('order_source', ['pos', 'qr', 'online', 'kiosk', 'call', 'whatsapp'])->default('pos');
            $table->string('online_order_id', 100)->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('product_name');
            $table->decimal('product_price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->string('unit_type', 50)->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('variant_name', 100)->nullable();
            $table->decimal('variant_price', 10, 2)->default(0);
            $table->decimal('addons_total', 10, 2)->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('item_total', 10, 2);
            $table->enum('item_status', ['pending', 'preparing', 'ready', 'served', 'cancelled'])->default('pending');
            $table->boolean('kot_printed')->default(false);
            $table->timestamp('kot_printed_at')->nullable();
            $table->timestamp('preparation_started_at')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->unsignedBigInteger('served_by')->nullable();
            $table->integer('estimated_preparation_time')->default(15);
            $table->integer('actual_preparation_time')->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->unsignedBigInteger('addon_id')->nullable();
            $table->string('addon_name', 100);
            $table->decimal('addon_price', 10, 2);
            $table->integer('quantity')->default(1);
        });

        // Add FK constraints that required both orders and tables to exist
        Schema::table('tables', function (Blueprint $table) {
            $table->foreign('current_order_id')->references('id')->on('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropForeign(['current_order_id']);
        });
        Schema::dropIfExists('order_item_addons');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
