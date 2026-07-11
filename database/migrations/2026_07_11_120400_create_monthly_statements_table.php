<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id')->constrained()->cascadeOnDelete();
            $table->date('bill_month'); // first day of month
            $table->timestamps();

            $table->unique(['flat_id', 'bill_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_statements');
    }
};
