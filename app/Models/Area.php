<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $table = 'areas';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'sigla',
        'area_padre_id',
        'recibe_documentos',
        'activo',
    ];

    protected $casts = [
        'recibe_documentos' => 'boolean',
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones jerárquicas
    |--------------------------------------------------------------------------
    */

    /**
     * Área superior o área padre.
     */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_padre_id'
        );
    }

    /**
     * Subáreas que dependen directamente de esta área.
     */
    public function hijos(): HasMany
    {
        return $this->hasMany(
            Area::class,
            'area_padre_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones con usuarios y cargos
    |--------------------------------------------------------------------------
    */

    /**
     * Asignaciones de usuarios/cargos realizadas en esta área.
     *
     * Esta relación funcionará cuando creemos el modelo UserCargo.
     */
    public function userCargos(): HasMany
    {
        return $this->hasMany(
            UserCargo::class,
            'area_id'
        );
    }

    public function correlativos(): HasMany
    {
        return $this->hasMany(
            Correlativo::class,
            'area_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Solo áreas activas.
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Solo áreas habilitadas para recibir documentos.
     */
    public function scopeQueRecibenDocumentos(Builder $query): Builder
    {
        return $query->where('recibe_documentos', true);
    }

    /**
     * Áreas principales, sin área padre.
     */
    public function scopeRaices(Builder $query): Builder
    {
        return $query->whereNull('area_padre_id');
    }
}
