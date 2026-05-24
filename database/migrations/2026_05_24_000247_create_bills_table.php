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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "May 2026 Gas & Water Bill"
            $table->date('bill_for_month')->nullable();
            $table->decimal('price_per_kg', 8, 2);  // Stores rate per kg
            $table->decimal('price_per_m3', 8, 2);  // Stores rate per cubic meter
            $table->decimal('total_used_m3', 12, 2)->default(0.00);
            $table->decimal('total_used_kg', 12, 2)->default(0.00);
            $table->decimal('total_bill', 12, 2)->default(0.00);
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
