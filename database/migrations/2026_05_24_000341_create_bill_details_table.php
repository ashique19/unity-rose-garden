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
        Schema::create('bill_details', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to link this detail to its main bill
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            
            // Foreign key linking to the flat
            $table->foreignId('flat_id')->constrained()->onDelete('cascade');
            
            // Readings & Calculations
            $table->decimal('previous_reading', 10, 2);
            $table->decimal('current_reading', 10, 2);
            $table->decimal('used_m3', 8, 2);
            $table->decimal('used_kg', 8, 2);
            
            // Time period tracker
            $table->date('bill_for_month'); // Keeps track of the targeted month (e.g. 2026-05-01)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_details');
    }
};
