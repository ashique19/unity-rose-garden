<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gas_meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id')->constrained()->cascadeOnDelete();
            $table->date('bill_month');
            $table->date('reading_date')->nullable();
            $table->decimal('previous_m3', 12, 2)->default(0);
            $table->decimal('current_m3', 12, 2)->default(0);
            $table->decimal('confirmed_m3', 12, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->decimal('gemini_suggestion', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['flat_id', 'bill_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gas_meter_readings');
    }
};
