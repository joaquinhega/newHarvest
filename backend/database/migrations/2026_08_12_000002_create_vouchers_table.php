<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('remito_code', 50)->nullable()->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('company_name', 150)->nullable();
            $table->foreignId('user_id')->constrained('users', 'id_usuario')->cascadeOnDelete();
            $table->string('passenger_name', 150);
            $table->string('origin', 255);
            $table->string('pickup_time', 15);
            $table->string('destination', 255);
            $table->string('dropoff_time', 15);
            $table->string('wait_time', 20)->nullable();
            $table->date('date');
            $table->decimal('amount', 15, 2)->nullable()->default(0.00);
            $table->text('observation')->nullable();
            $table->string('signature_path', 255)->nullable();
            $table->enum('status', ['pendiente', 'aprobado'])->default('pendiente');
            $table->boolean('borrado')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'company_id', 'status', 'borrado', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};