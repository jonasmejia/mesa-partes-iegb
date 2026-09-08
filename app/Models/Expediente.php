<?php

namespace App\Models;

use App\Enums\EstadoCodigo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expediente extends Model
{
    protected $table = 'expedientes';

    protected $fillable = [
        'codigo',
        'anio',
        'asunto',
        'estado_id',
        'area_actual_id',
        'creado_por',
        'fecha_apertura',
        'fecha_cierre',
    ];

    protected $casts = [
        'anio' => 'integer',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Estado actual del expediente.
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(
            Estado::class,
            'estado_id'
        );
    }

    /**
     * Área donde actualmente se encuentra el expediente.
     */
    public function areaActual(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_actual_id'
        );
    }

    /**
     * Usuario que creó el expediente.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'creado_por'
        );
    }

    public function estadoCodigo(): EstadoCodigo
    {
        return EstadoCodigo::from(
            $this->estado->codigo
        );
    }

    public function puedeCambiarEstadoA(
        EstadoCodigo $nuevoEstado
    ): bool {
        return $this->estadoCodigo()
            ->puedeCambiarA($nuevoEstado);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Expedientes de un año determinado.
     */
    public function scopeDelAnio(
        Builder $query,
        int $anio
    ): Builder {
        return $query->where('anio', $anio);
    }

    /**
     * Expedientes abiertos.
     */
    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->whereNull('fecha_cierre');
    }

    /**
     * Expedientes cerrados.
     */
    public function scopeCerrados(Builder $query): Builder
    {
        return $query->whereNotNull('fecha_cierre');
    }

    /**
     * Registros intermedios expediente-documento.
     */
    public function expedienteDocumentos(): HasMany
    {
        return $this->hasMany(
            ExpedienteDocumento::class,
            'expediente_id'
        );
    }

    /**
     * Documentos asociados al expediente.
     */
    public function documentos(): BelongsToMany
    {
        return $this->belongsToMany(
            Documento::class,
            'expediente_documentos',
            'expediente_id',
            'documento_id'
        )
            ->withPivot([
                'id',
                'relacion',
                'orden',
            ])
            ->withTimestamps()
            ->orderByPivot('orden');
    }
}
