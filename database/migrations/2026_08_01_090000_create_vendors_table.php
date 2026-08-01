<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('note', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        if (Schema::hasTable('account_ledger_entries') && ! Schema::hasColumn('account_ledger_entries', 'vendor_id')) {
            Schema::table('account_ledger_entries', function (Blueprint $table) {
                $table->foreignId('vendor_id')->nullable()->after('payee')->constrained('vendors')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('account_ledger_entries') && Schema::hasColumn('account_ledger_entries', 'vendor_id')) {
            Schema::table('account_ledger_entries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('vendor_id');
            });
        }

        Schema::dropIfExists('vendors');
    }
};
