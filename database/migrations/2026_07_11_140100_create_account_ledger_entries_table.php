<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // cash_in | cash_out
            $table->decimal('amount', 12, 2);
            $table->date('entry_date');
            $table->foreignId('flat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_ledger_entries');
    }
};
