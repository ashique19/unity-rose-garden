<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_heads', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('account_ledger_entries', function (Blueprint $table) {
            $table->foreignId('expense_head_id')->nullable()->after('category')->constrained()->nullOnDelete();
            $table->string('payee')->nullable()->after('expense_head_id');
        });

        $now = now();
        $heads = [
            ['key' => 'salary', 'label' => 'Salary', 'sort_order' => 10],
            ['key' => 'repair', 'label' => 'Repair', 'sort_order' => 20],
            ['key' => 'supplies', 'label' => 'Supplies', 'sort_order' => 30],
            ['key' => 'common_electricity_bill', 'label' => 'Common electricity bill', 'sort_order' => 40],
            ['key' => 'gas_purchase', 'label' => 'Gas purchase', 'sort_order' => 50],
            ['key' => 'water_bill', 'label' => 'Water bill', 'sort_order' => 60],
            ['key' => 'cleaner', 'label' => 'Cleaner', 'sort_order' => 70],
            ['key' => 'garbage', 'label' => 'Garbage', 'sort_order' => 80],
            ['key' => 'maintenance', 'label' => 'Maintenance', 'sort_order' => 90],
            ['key' => 'utility', 'label' => 'Utility', 'sort_order' => 100],
            ['key' => 'misc', 'label' => 'Misc', 'sort_order' => 110],
        ];

        foreach ($heads as $head) {
            DB::table('expense_heads')->insert([
                ...$head,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $map = DB::table('expense_heads')->pluck('id', 'key');

        foreach (DB::table('account_ledger_entries')->where('type', 'cash_out')->whereNotNull('category')->get() as $entry) {
            $key = strtolower((string) $entry->category);
            $aliases = [
                'maintenance' => 'maintenance',
                'salary' => 'salary',
                'utility' => 'utility',
                'supplies' => 'supplies',
                'misc' => 'misc',
                'donation' => 'misc',
            ];
            $headKey = $aliases[$key] ?? null;
            if ($headKey && isset($map[$headKey])) {
                DB::table('account_ledger_entries')->where('id', $entry->id)->update([
                    'expense_head_id' => $map[$headKey],
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('account_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_head_id');
            $table->dropColumn('payee');
        });

        Schema::dropIfExists('expense_heads');
    }
};
