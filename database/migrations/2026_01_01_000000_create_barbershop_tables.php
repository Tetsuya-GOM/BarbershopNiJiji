<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('contact_number', 30);
            $table->string('email')->nullable()->index();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['last_name', 'first_name']);
        });
        Schema::create('barbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('contact_number', 30);
            $table->string('email')->unique();
            $table->string('specialization')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->index(['last_name', 'first_name']);
        });
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('duration_minutes');
            $table->string('status')->default('available')->index();
            $table->timestamps();
        });
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('barber_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->date('appointment_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('pending')->index();
            $table->string('service_name_snapshot');
            $table->decimal('service_price_snapshot', 10, 2);
            $table->unsignedInteger('service_duration_snapshot');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['barber_id', 'appointment_date', 'start_time']);
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance', 10, 2);
            $table->string('payment_status')->default('unpaid')->index();
            $table->timestamps();
        });
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->timestamp('paid_at')->index();
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('services');
        Schema::dropIfExists('barbers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('settings');
    }
};
