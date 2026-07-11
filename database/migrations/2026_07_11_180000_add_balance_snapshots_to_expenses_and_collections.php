<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('balance_before', 12, 2)->nullable()->after('note');
            $table->decimal('balance_after', 12, 2)->nullable()->after('balance_before');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->decimal('balance_before', 12, 2)->nullable()->after('note');
            $table->decimal('balance_after', 12, 2)->nullable()->after('balance_before');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['balance_before', 'balance_after']);
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['balance_before', 'balance_after']);
        });
    }
};
