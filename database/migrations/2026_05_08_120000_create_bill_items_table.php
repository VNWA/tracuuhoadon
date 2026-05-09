<?php

use App\Models\Bill;
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
        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Bill::class)->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('unit')->nullable();
            $table->string('quantity')->nullable();
            $table->string('unit_price')->nullable();
            $table->string('amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
