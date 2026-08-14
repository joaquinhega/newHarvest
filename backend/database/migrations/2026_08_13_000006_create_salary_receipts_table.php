<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('period', 50); // Ej: "Junio 2026", "2026-06"
            $table->decimal('gross_amount', 15, 2)->default(0.00); // Sueldo Bruto
            $table->decimal('deductions_amount', 15, 2)->default(0.00); // Retenciones / Descuentos
            $table->decimal('net_amount', 15, 2); // Sueldo Neto a cobrar
            $table->enum('status', [
                'generado', 
                'notificado', 
                'leido', 
                'firmado_empresa', 
                'firmado_empleado', 
                'archivado'
            ])->default('generado');
            $table->string('file_path', 255)->nullable(); // Archivo PDF o enlace a Google Drive
            $table->string('employer_signature_path', 255)->nullable();
            $table->timestamp('employer_signed_at')->nullable();
            $table->string('employee_signature_path', 255)->nullable();
            $table->timestamp('employee_signed_at')->nullable();
            $table->boolean('legal_accepted')->default(false); // Declaración de conformidad
            $table->boolean('borrado')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'status', 'period', 'borrado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_receipts');
    }
};