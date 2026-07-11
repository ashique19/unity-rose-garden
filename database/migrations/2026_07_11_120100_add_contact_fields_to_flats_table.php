<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flats', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('name');
            $table->string('phone', 11)->nullable()->after('contact_name');
            $table->enum('status', ['online', 'offline'])->default('online')->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('flats', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'phone', 'status']);
        });
    }
};
