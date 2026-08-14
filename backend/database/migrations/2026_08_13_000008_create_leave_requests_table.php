<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['vacaciones', 'certificado_medico', 'licencia_especial']);
            $table->unsignedSmallInteger('days_count');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('diagnosis', 255)->nullable(); // Diagnóstico o comentarios
            $table->string('attachment_path', 255)->nullable(); // Foto/PDF del certificado médico
            $table->enum('status', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users', 'id_usuario')->nullOnDelete();
            $table->timestamp('action_at')->nullable(); // Fecha de aprobación/rechazo
            $table->boolean('borrado')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'type', 'status', 'borrado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};