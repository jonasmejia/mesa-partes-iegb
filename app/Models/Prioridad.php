<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Prioridad extends Model
{
    protected $table = 'prioridades';

    protected $fillable = [
        'codigo',
        'nombre',
        'nivel',
        'horas_objetivo',
        'activo',
    ];

    protected $casts = [
        'nivel' => 'integer',
        'horas_objetivo' => 'integer',
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Solo prioridades activas.
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Ordena las prioridades por nivel ascendente.
     */
    public function scopePorNivel(Builder $query): Builder
    {
        return $query->orderBy('nivel');
    }

    /**
     * Prioridades que tienen tiempo objetivo definido.
     */
    public function scopeConHorasObjetivo(Builder $query): Builder
    {
        return $query->whereNotNull('horas_objetivo');
    }
}