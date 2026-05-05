<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('table_sections')->nullOnDelete();
            $table->string('name', 50);
            $table->string('table_number', 50);
            $table->integer('capacity')->default(4);
            $table->integer('minimum_capacity')->default(1);
            $table->enum('table_type', ['regular', 'couple', 'family', 'party', 'private', 'bar', 'outdoor', 'rooftop'])->default('regular');
            $table->integer('position_x')->nullable();
            $table->integer('position_y')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->enum('shape', ['circle', 'square', 'rectangle', 'oval', 'custom'])->default('rectangle');
            $table->integer('rotation')->default(0);
            $table->enum('status', ['available', 'occupied', 'reserved', 'merged', 'blocked', 'cleaning', 'maintenance'])->default('available');
            $table->unsignedBigInteger('current_order_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta_data')->nullable();
            $table->timestamps();
        });

        Schema::create('table_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('table_id')->constrained('tables');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();
            $table->integer('number_of_guests');
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->integer('duration_minutes')->default(120);
            $table->text('special_requests')->nullable();
            $table->string('occasion', 100)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'arrived', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_reservations');
        Schema::dropIfExists('tables');
        Schema::dropIfExists('table_sections');
    }
};
