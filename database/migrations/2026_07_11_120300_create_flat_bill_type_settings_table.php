<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flat_bill_type_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['flat_id', 'bill_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flat_bill_type_settings');
    }
};
