<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_receipt_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_receipt_id')->constrained('salary_receipts')->cascadeOnDelete();
            $table->uuid('batch_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 150)->nullable();
            $table->string('event', 50);
            $table->char('file_hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id_usuario')
                ->on('users')
                ->nullOnDelete();

            $table->index(['salary_receipt_id', 'event', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_receipt_audits');
    }
};