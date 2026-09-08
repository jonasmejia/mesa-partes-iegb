<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subsanaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('observacion_id');
            $table->unsignedBigInteger('documento_id');
            $table->unsignedBigInteger('registrado_por')->nullable();
            $table->text('detalle');
            $table->dateTime('fecha_subsanacion')->useCurrent();
            $table->string('estado_revision', 30)->default('PENDIENTE')->index();
            $table->unsignedBigInteger('revisado_por')->nullable();
            $table->dateTime('fecha_revision')->nullable();
            $table->text('observacion_revision')->nullable();
            $table->timestamps();

            $table->foreign('observacion_id', 'sub_obs_fk')->references('id')->on('observaciones')->cascadeOnDelete();
            $table->foreign('documento_id', 'sub_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
            $table->foreign('registrado_por', 'sub_reg_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('revisado_por', 'sub_rev_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsanaciones');
    }
};
