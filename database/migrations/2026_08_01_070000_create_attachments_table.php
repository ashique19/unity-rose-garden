<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('original_name')->nullable();
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size_bytes')->default(0);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->date('bill_month')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('bill_month');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
