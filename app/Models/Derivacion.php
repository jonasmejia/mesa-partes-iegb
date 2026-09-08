<?php

namespace App\Models;

use App\Enums\EstadoCodigo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Derivacion extends Model
{
    protected $table = 'derivaciones';

    protected $fillable = [
        'documento_id',
        'area_origen_id',
        'area_destino_id',
        'derivado_por',
        'responsable_destino_id',
        'estado_id',
        'indicacion',
        'fecha_derivacion',
        'fecha_limite',
        'fecha_recepcion',
        'fecha_atencion',
    ];

    protected $casts = [
        'fecha_derivacion' => 'datetime',
        'fecha_limite' => 'datetime',
        'fecha_recepcion' => 'datetime',
        'fecha_atencion' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function documento(): BelongsTo
    {
        return $this->belongsTo(
            Documento::class,
            'documento_id'
        );
    }

    public function areaOrigen(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_origen_id'
        );
    }

    public function areaDestino(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_destino_id'
        );
    }

    public function derivadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'derivado_por'
        );
    }

    public function responsableDestino(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'responsable_destino_id'
        );
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(
            Estado::class,
            'estado_id'
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

    public function scopePendientesRecepcion(Builder $query): Builder
    {
        return $query->whereNull('fecha_recepcion');
    }

    public function scopeRecepcionadas(Builder $query): Builder
    {
        return $query->whereNotNull('fecha_recepcion');
    }

    public function scopePendientesAtencion(Builder $query): Builder
    {
        return $query
            ->whereNotNull('fecha_recepcion')
            ->whereNull('fecha_atencion');
    }

    public function scopeAtendidas(Builder $query): Builder
    {
        return $query->whereNotNull('fecha_atencion');
    }

    public function scopeVencidas(Builder $query): Builder
    {
        return $query
            ->whereNotNull('fecha_limite')
            ->where('fecha_limite', '<', now())
            ->whereNull('fecha_atencion');
    }

    public function scopeDelAreaDestino(
        Builder $query,
        int $areaId
    ): Builder {
        return $query->where(
            'area_destino_id',
            $areaId
        );
    }

    public function scopeDelDocumento(
        Builder $query,
        int $documentoId
    ): Builder {
        return $query->where(
            'documento_id',
            $documentoId
        );
    }

    public function scopeCronologicas(Builder $query): Builder
    {
        return $query->orderBy('fecha_derivacion');
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('fecha_derivacion');
    }

    /**
     * Archivos adjuntos específicamente a esta derivación.
     */
    public function archivos(): HasMany
    {
        return $this->hasMany(
            DerivacionArchivo::class,
            'derivacion_id'
        );
    }

    public function actuaciones(): HasMany
    {
        return $this->hasMany(
            Actuacion::class,
            'documento_id'
        );
    }

    /**
     * Recepción formal de la derivación.
     */
    public function recepcion(): HasOne
    {
        return $this->hasOne(
            Recepcion::class,
            'derivacion_id'
        );
    }
}
