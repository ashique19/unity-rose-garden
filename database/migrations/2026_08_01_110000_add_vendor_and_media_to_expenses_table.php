<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('payee')->constrained('vendors')->nullOnDelete();
            }
            if (! Schema::hasColumn('expenses', 'media')) {
                $table->json('media')->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'media')) {
                $table->dropColumn('media');
            }
            if (Schema::hasColumn('expenses', 'vendor_id')) {
                $table->dropConstrainedForeignId('vendor_id');
            }
        });
    }
};
