<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id');
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('registrado_por')->nullable();
            $table->string('tipo', 40)->default('GENERAL')->index();
            $table->text('detalle');
            $table->boolean('requiere_subsanacion')->default(false);
            $table->dateTime('fecha_observacion')->useCurrent();
            $table->dateTime('fecha_subsanacion_limite')->nullable();
            $table->timestamps();

            $table->foreign('documento_id', 'obs_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
            $table->foreign('area_id', 'obs_area_fk')->references('id')->on('areas')->nullOnDelete();
            $table->foreign('registrado_por', 'obs_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones');
    }
};
