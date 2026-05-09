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
            $table->string('private_key')->unique();
            $table->string('date')->nullable();
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->string('sell_mst')->default('0301045759-022');
            $table->string('customer_name')->nullable();
            $table->string('unit_name')->nullable();
            $table->string('customer_mst')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_cccd')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('note')->nullable();
            $table->string('bill_total_currency')->nullable();
            $table->string('bill_total_text')->nullable();
            $table->string('path')->nullable();
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
