<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_head_id')->constrained('expense_heads')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('entry_date');
            $table->string('payee', 120)->nullable();
            $table->string('note', 255);
            $table->timestamps();

            $table->index(['entry_date', 'expense_head_id']);
        });

        Schema::table('account_ledger_entries', function (Blueprint $table) {
            $table->foreignId('expense_id')
                ->nullable()
                ->after('collection_id')
                ->constrained('expenses')
                ->nullOnDelete();
        });

        $cashOuts = DB::table('account_ledger_entries')
            ->where('type', 'cash_out')
            ->whereNotNull('expense_head_id')
            ->whereNull('collection_id')
            ->orderBy('id')
            ->get();

        foreach ($cashOuts as $row) {
            $expenseId = DB::table('expenses')->insertGetId([
                'expense_head_id' => $row->expense_head_id,
                'amount' => $row->amount,
                'entry_date' => $row->entry_date,
                'payee' => $row->payee,
                'note' => $row->note ?: ($row->category ?: 'Expense'),
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);

            DB::table('account_ledger_entries')
                ->where('id', $row->id)
                ->update(['expense_id' => $expenseId]);
        }
    }

    public function down(): void
    {
        Schema::table('account_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
        });

        Schema::dropIfExists('expenses');
    }
};
