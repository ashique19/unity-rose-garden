<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charge_templates', function (Blueprint $table) {
            $table->foreignId('bill_type_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('custom_charges', function (Blueprint $table) {
            $table->foreignId('bill_type_id')->nullable()->after('flat_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('custom_charges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bill_type_id');
        });

        Schema::table('charge_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bill_type_id');
        });
    }
};
