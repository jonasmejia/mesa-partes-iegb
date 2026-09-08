<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correlativos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 40);
            $table->unsignedSmallInteger('anio');
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('tipo_documento_id')->nullable();
            $table->unsignedBigInteger('ultimo_numero')->default(0);
            /*
            |--------------------------------------------------------------------------
            | Columnas generadas para garantizar unicidad con NULL
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('area_scope')
                ->storedAs('COALESCE(area_id, 0)');

            $table->unsignedBigInteger('tipo_documento_scope')
                ->storedAs('COALESCE(tipo_documento_id, 0)');

            $table->timestamps();



            $table->foreign('area_id', 'corr_area_fk')->references('id')->on('areas')->restrictOnDelete();
            $table->foreign('tipo_documento_id', 'corr_tipo_doc_fk')->references('id')->on('tipos_documento')->restrictOnDelete();

            $table->unique(['tipo', 'anio', 'area_scope', 'tipo_documento_scope'], 'corr_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correlativos');
    }
};
