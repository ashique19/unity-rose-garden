<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Predefined Charge Headings Template Table
        Schema::create('charge_templates', function (Blueprint $table) {
            $table->id();
            $table->string('charge_key')->unique(); // 'service_charge', 'garbage_fee', 'late_penalty'
            $table->string('label');             // 'Building Service Charge', 'Late Payment Penalty'
            $table->decimal('default_amount', 10, 2)->default(0.00);
            $table->boolean('is_building_wide')->default(true); // True = applies to all flats automatically
            $table->timestamps();
        });

        // 2. Exceptional / Custom Charges Table (Flat & Month Specific)
        Schema::create('custom_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id')->constrained()->cascadeOnDelete();
            $table->date('charge_month');        // Maps to the billing month (e.g., '2026-07-01')
            $table->string('label');             // In case of completely custom ad-hoc notes
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();   // Reason: "Damaged common area pipe repair"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_charges');
        Schema::dropIfExists('charge_templates');
    }
};