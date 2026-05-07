<?php

use App\Models\User;
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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('bill_symbol')->unique();
            $table->string('bill_number')->nullable();
            $table->string('bill_date')->nullable();
            $table->string('bill_month')->nullable();
            $table->string('bill_year')->nullable();
            $table->string('bill_private_key')->unique();
            $table->string('bill_sell_mst')->default('0301045759');
            $table->string('customer_name')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_cccd_number')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('payment_method')->default('Chuyển khoản');
            $table->string('total_amount')->nullable();
            $table->string('bill_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
