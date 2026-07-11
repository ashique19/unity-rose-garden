<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_statement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bill_type_key');
            $table->string('label');
            $table->decimal('quantity', 12, 4)->nullable();
            $table->decimal('rate', 12, 4)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('meta')->nullable(); // gas snapshot fields, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_lines');
    }
};
