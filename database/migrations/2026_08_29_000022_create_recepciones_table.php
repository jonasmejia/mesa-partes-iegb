<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('derivacion_id')->unique();
            $table->unsignedBigInteger('recibido_por');
            $table->string('resultado', 30)->default('RECIBIDO');
            $table->text('observacion')->nullable();
            $table->dateTime('fecha_recepcion')->useCurrent();
            $table->timestamps();

            $table->foreign('derivacion_id', 'rec_der_fk')->references('id')->on('derivaciones')->cascadeOnDelete();
            $table->foreign('recibido_por', 'rec_user_fk')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepciones');
    }
};
