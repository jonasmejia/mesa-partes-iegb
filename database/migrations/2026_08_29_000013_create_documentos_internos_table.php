<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_internos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id')->unique();
            $table->unsignedBigInteger('area_origen_id');
            $table->unsignedBigInteger('emisor_user_id');
            $table->boolean('requiere_firma')->default(false);
            $table->dateTime('fecha_emision')->nullable();
            $table->timestamps();

            $table->foreign('documento_id', 'di_doc_fk')->references('id')->on('documentos')->cascadeOnDelete();
            $table->foreign('area_origen_id', 'di_area_fk')->references('id')->on('areas')->restrictOnDelete();
            $table->foreign('emisor_user_id', 'di_emisor_fk')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_internos');
    }
};
