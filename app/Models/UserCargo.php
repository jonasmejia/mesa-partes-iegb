<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCargo extends Model
{
    protected $table = 'user_cargos';

    protected $fillable = [
        'user_id',
        'cargo_id',
        'area_id',
        'fecha_inicio',
        'fecha_fin',
        'es_responsable',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'es_responsable' => 'boolean',
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Usuario al que pertenece esta asignación.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * Cargo asignado al usuario.
     */
    public function cargo(): BelongsTo
    {
        return $this->belongsTo(
            Cargo::class,
            'cargo_id'
        );
    }

    /**
     * Área donde el usuario ejerce el cargo.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Solo asignaciones activas.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Solo asignaciones vigentes por fechas.
     */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query
            ->where('fecha_inicio', '<=', today())
            ->where(function (Builder $query) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', today());
            });
    }

    /**
     * Solo responsables de área.
     */
    public function scopeResponsables(Builder $query): Builder
    {
        return $query->where('es_responsable', true);
    }

    /**
     * Asignaciones activas y vigentes.
     */
    public function scopeAsignacionesActuales(Builder $query): Builder
    {
        return $query
            ->activos()
            ->vigentes();
    }
}