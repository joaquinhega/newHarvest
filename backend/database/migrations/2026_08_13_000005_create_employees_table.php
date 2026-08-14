<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); // Representa el Número de Legajo
            $table->unsignedBigInteger('user_id')->nullable()->unique(); // Foreign Key 1:1 opcional
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('cuil', 20)->unique(); // Formato: 20-43942223-9
            $table->string('position', 100); // Puesto: Chofer, Chofer inicial, RRHH, etc.
            $table->date('hire_date')->nullable(); // Fecha de ingreso para cálculo de antigüedad
            $table->date('birth_date')->nullable(); // Fecha de nacimiento
            $table->string('phone', 50)->nullable();
            $table->string('address', 255)->nullable();
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
            $table->boolean('borrado')->default(false); // Baja lógica
            $table->timestamps();

            // Clave foránea referenciando 'id_usuario' en la tabla 'users'
            $table->foreign('user_id')
                ->references('id_usuario')
                ->on('users')
                ->nullOnDelete();

            // Índices de búsqueda optimizados
            $table->index(['status', 'borrado', 'position', 'cuil']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};