<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('sanction_number', 50)->nullable(); // Ej: "287"
            $table->enum('type', ['apercibimiento', 'suspension']);
            $table->unsignedSmallInteger('days_count')->default(0); // Días efectivos si es suspensión
            $table->date('date');
            $table->text('reason'); // Motivo de la sanción
            $table->string('file_path', 255)->nullable();
            $table->enum('status', ['pendiente', 'leido', 'firmado', 'archivado'])->default('pendiente');
            $table->timestamp('read_at')->nullable();
            $table->string('signature_path', 255)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->boolean('borrado')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'type', 'status', 'borrado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanctions');
    }
};