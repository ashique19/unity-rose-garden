<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attachments')) {
            return;
        }

        Schema::table('attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('attachments', 'public_token')) {
                $table->string('public_token', 64)->nullable()->unique()->after('id');
            }
        });

        $rows = DB::table('attachments')->whereNull('public_token')->pluck('id');
        foreach ($rows as $id) {
            DB::table('attachments')->where('id', $id)->update([
                'public_token' => Str::random(40),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('attachments') || ! Schema::hasColumn('attachments', 'public_token')) {
            return;
        }

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
