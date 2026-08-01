<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_ledger_entries')) {
            return;
        }

        Schema::table('account_ledger_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('account_ledger_entries', 'media')) {
                $table->json('media')->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('account_ledger_entries') || ! Schema::hasColumn('account_ledger_entries', 'media')) {
            return;
        }

        Schema::table('account_ledger_entries', function (Blueprint $table) {
            $table->dropColumn('media');
        });
    }
};
