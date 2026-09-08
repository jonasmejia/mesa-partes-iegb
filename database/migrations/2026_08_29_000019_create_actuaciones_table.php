<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actuaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id');
            $table->unsignedBigInteger('derivacion_id')->nullable();
            $table->unsignedBigInteger('tipo_actuacion_id');
            $table->unsignedBigInteger('area_id');
            $table->unsignedBigInteger('realizado_por');
            $table->unsignedBigInteger('estado_resultante_id')->nullable();
            $table->string('asunto', 255)->nullable();
            $table->text('detalle');
            $table->dateTime('fecha_actuacion')->useCurrent();
            $table->boolean('visible_externo')->default(false);
            $table->timestamps();

            $table->foreign('documento_id', 'act_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
            $table->foreign('derivacion_id', 'act_der_fk')->references('id')->on('derivaciones')->nullOnDelete();
            $table->foreign('tipo_actuacion_id', 'act_tipo_fk')->references('id')->on('tipos_actuacion')->restrictOnDelete();
            $table->foreign('area_id', 'act_area_fk')->references('id')->on('areas')->restrictOnDelete();
            $table->foreign('realizado_por', 'act_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('estado_resultante_id', 'act_estado_fk')->references('id')->on('estados')->nullOnDelete();

            $table->index(['documento_id', 'fecha_actuacion'], 'act_doc_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actuaciones');
    }
};
