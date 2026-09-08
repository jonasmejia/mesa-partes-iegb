<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('sigla', 30)->nullable();
            $table->unsignedBigInteger('area_padre_id')->nullable();
            $table->boolean('recibe_documentos')->default(true);
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();

            $table->foreign('area_padre_id', 'areas_padre_fk')
                ->references('id')->on('areas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
