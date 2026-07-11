<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('common_meter_readings', function (Blueprint $table) {
            $table->id();
            $table->string('meter_key')->default('water'); // water, etc.
            $table->date('bill_month');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('previous_reading', 12, 2)->nullable();
            $table->decimal('current_reading', 12, 2)->nullable();
            $table->date('reading_date')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['meter_key', 'bill_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('common_meter_readings');
    }
};
