<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoActuacion extends Model
{
    protected $table = 'tipos_actuacion';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'cierra_tramite',
        'activo',
    ];

    protected $casts = [
        'cierra_tramite' => 'boolean',
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Solo tipos de actuación activos.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Actuaciones que permiten cerrar/finalizar el trámite.
     */
    public function scopeQueCierranTramite(Builder $query): Builder
    {
        return $query->where('cierra_tramite', true);
    }

    /**
     * Actuaciones que no cierran el trámite.
     */
    public function scopeQueNoCierranTramite(Builder $query): Builder
    {
        return $query->where('cierra_tramite', false);
    }

    public function actuaciones(): HasMany
    {
        return $this->hasMany(
            Actuacion::class,
            'tipo_actuacion_id'
        );
    }
}
