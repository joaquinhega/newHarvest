<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combustibles', function (Blueprint $table) {
            $table->id();
            $table->string('remito_code', 50)->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('driver_name', 150)->nullable();
            $table->string('plate', 20);
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pendiente', 'aprobado'])->default('pendiente');
            $table->boolean('borrado')->default(false);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id_usuario')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['user_id', 'status', 'borrado', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combustibles');
    }
};