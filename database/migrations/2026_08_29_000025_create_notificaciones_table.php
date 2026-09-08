<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('remitente_externo_id')->nullable();
            $table->string('canal', 30)->index();
            $table->string('destino', 191);
            $table->string('asunto', 255);
            $table->text('mensaje');
            $table->string('estado_envio', 30)->default('PENDIENTE')->index();
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->dateTime('fecha_programada')->nullable();
            $table->dateTime('fecha_envio')->nullable();
            $table->text('error_envio')->nullable();
            $table->timestamps();

            $table->foreign('documento_id', 'not_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
            $table->foreign('user_id', 'not_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('remitente_externo_id', 'not_rem_fk')->references('id')->on('remitentes_externos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
