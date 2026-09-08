<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('evento', 60)->index();
            $table->string('tabla', 100)->nullable()->index();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->dateTime('fecha')->useCurrent()->index();
            $table->timestamps();

            $table->foreign('user_id', 'aud_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['tabla', 'registro_id'], 'aud_tabla_registro_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
