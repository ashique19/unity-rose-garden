<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bill_types')) {
            return;
        }

        $now = now();

        $garbageTypeId = DB::table('bill_types')->where('key', 'garbage')->value('id');

        if (! $garbageTypeId) {
            $garbageTypeId = DB::table('bill_types')->insertGetId([
                'key' => 'garbage',
                'label' => 'Garbage',
                'nature' => 'other',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('flat_bill_type_settings') && Schema::hasTable('flats')) {
            $flatIds = DB::table('flats')->pluck('id');
            foreach ($flatIds as $flatId) {
                $exists = DB::table('flat_bill_type_settings')
                    ->where('flat_id', $flatId)
                    ->where('bill_type_id', $garbageTypeId)
                    ->exists();

                if (! $exists) {
                    DB::table('flat_bill_type_settings')->insert([
                        'flat_id' => $flatId,
                        'bill_type_id' => $garbageTypeId,
                        'enabled' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if (! Schema::hasTable('charge_templates')) {
            return;
        }

        $template = DB::table('charge_templates')->where('charge_key', 'garbage')->first();

        if ($template) {
            if ($template->bill_type_id === null) {
                DB::table('charge_templates')
                    ->where('id', $template->id)
                    ->update([
                        'bill_type_id' => $garbageTypeId,
                        'is_building_wide' => true,
                        'updated_at' => $now,
                    ]);
            }
        } else {
            DB::table('charge_templates')->insert([
                'bill_type_id' => $garbageTypeId,
                'charge_key' => 'garbage',
                'label' => 'Garbage',
                'default_amount' => 100,
                'is_building_wide' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Keep garbage bill type / template data; removal would break existing statements.
    }
};
