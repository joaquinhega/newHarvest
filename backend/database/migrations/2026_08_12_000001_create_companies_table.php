<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('path')->nullable();
            $table->boolean('borrado')->default(false);
            $table->timestamps();

            $table->index(['name', 'borrado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};