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
        $water = DB::table('bill_types')->where('key', 'water')->first();
        $wasa = DB::table('bill_types')->where('key', 'wasa')->first();
        $deep = DB::table('bill_types')->where('key', 'deep_tubewell')->first();

        if ($water && ! $wasa) {
            DB::table('bill_types')->where('id', $water->id)->update([
                'key' => 'wasa',
                'label' => 'WASA',
                'nature' => 'meter_common',
                'sort_order' => 3,
                'updated_at' => $now,
            ]);
            $wasaId = $water->id;
        } elseif ($wasa) {
            $wasaId = $wasa->id;
            DB::table('bill_types')->where('id', $wasaId)->update([
                'label' => 'WASA',
                'nature' => 'meter_common',
                'is_active' => true,
                'sort_order' => 3,
                'updated_at' => $now,
            ]);
        } else {
            $wasaId = DB::table('bill_types')->insertGetId([
                'key' => 'wasa',
                'label' => 'WASA',
                'nature' => 'meter_common',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($water && $wasa && (int) $water->id !== (int) $wasa->id) {
            // Rare: both existed — point water refs at wasa, then drop water type.
            $this->repointBillType($water->id, $wasaId);
            DB::table('bill_types')->where('id', $water->id)->delete();
        }

        if (! $deep) {
            $deepId = DB::table('bill_types')->insertGetId([
                'key' => 'deep_tubewell',
                'label' => 'Deep tube-well',
                'nature' => 'meter_common',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $deepId = $deep->id;
            DB::table('bill_types')->where('id', $deepId)->update([
                'label' => 'Deep tube-well',
                'nature' => 'meter_common',
                'is_active' => true,
                'sort_order' => 2,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('common_meter_readings')) {
            DB::table('common_meter_readings')
                ->where('meter_key', 'water')
                ->update(['meter_key' => 'wasa', 'updated_at' => $now]);
        }

        if (Schema::hasTable('statement_lines')) {
            DB::table('statement_lines')
                ->where('bill_type_key', 'water')
                ->update([
                    'bill_type_key' => 'wasa',
                    'bill_type_id' => $wasaId,
                    'updated_at' => $now,
                ]);
        }

        if (Schema::hasTable('flat_bill_type_settings') && Schema::hasTable('flats')) {
            $flatIds = DB::table('flats')->pluck('id');
            foreach ($flatIds as $flatId) {
                foreach ([$wasaId, $deepId] as $typeId) {
                    $exists = DB::table('flat_bill_type_settings')
                        ->where('flat_id', $flatId)
                        ->where('bill_type_id', $typeId)
                        ->exists();
                    if (! $exists) {
                        DB::table('flat_bill_type_settings')->insert([
                            'flat_id' => $flatId,
                            'bill_type_id' => $typeId,
                            'enabled' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }

        // Keep cleaner / common_electricity / garbage sort order after water types.
        DB::table('bill_types')->where('key', 'cleaner')->update(['sort_order' => 4, 'updated_at' => $now]);
        DB::table('bill_types')->where('key', 'common_electricity')->update(['sort_order' => 5, 'updated_at' => $now]);
        DB::table('bill_types')->where('key', 'garbage')->update(['sort_order' => 6, 'updated_at' => $now]);
    }

    public function down(): void
    {
        // Irreversible data reshape; keep dual water types.
    }

    private function repointBillType(int $fromId, int $toId): void
    {
        if (Schema::hasTable('statement_lines')) {
            DB::table('statement_lines')->where('bill_type_id', $fromId)->update(['bill_type_id' => $toId]);
        }
        if (Schema::hasTable('custom_charges')) {
            DB::table('custom_charges')->where('bill_type_id', $fromId)->update(['bill_type_id' => $toId]);
        }
        if (Schema::hasTable('charge_templates')) {
            DB::table('charge_templates')->where('bill_type_id', $fromId)->update(['bill_type_id' => $toId]);
        }
        if (Schema::hasTable('flat_bill_type_settings')) {
            $settings = DB::table('flat_bill_type_settings')->where('bill_type_id', $fromId)->get();
            foreach ($settings as $setting) {
                $exists = DB::table('flat_bill_type_settings')
                    ->where('flat_id', $setting->flat_id)
                    ->where('bill_type_id', $toId)
                    ->exists();
                if ($exists) {
                    DB::table('flat_bill_type_settings')->where('id', $setting->id)->delete();
                } else {
                    DB::table('flat_bill_type_settings')
                        ->where('id', $setting->id)
                        ->update(['bill_type_id' => $toId]);
                }
            }
        }
    }
};
