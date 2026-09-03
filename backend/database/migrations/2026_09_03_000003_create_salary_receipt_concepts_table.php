<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_receipt_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_receipt_id')->constrained('salary_receipts')->cascadeOnDelete();

            $table->string('code', 10)->nullable();        // Ej: "096" (COD)
            $table->string('description', 150);             // Ej: "TURNOS" (CONCEPTO)
            $table->decimal('quantity', 10, 2)->nullable();  // Ej: 24.00 (CANTIDAD)

            // Un concepto puede aportar a una sola de estas tres columnas, igual que en el recibo real.
            $table->decimal('remunerative_amount', 15, 2)->default(0.00);     // REM C/D
            $table->decimal('non_remunerative_amount', 15, 2)->default(0.00); // NO REM
            $table->decimal('deduction_amount', 15, 2)->default(0.00);        // DEDUCCIONES

            $table->unsignedSmallInteger('sort_order')->default(0); // Orden de aparición en el PDF
            $table->timestamps();

            $table->index(['salary_receipt_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_receipt_concepts');
    }
};
