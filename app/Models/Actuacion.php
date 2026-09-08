<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Actuacion extends Model
{
    protected $table = 'actuaciones';

    protected $fillable = [
        'documento_id',
        'derivacion_id',
        'tipo_actuacion_id',
        'area_id',
        'realizado_por',
        'estado_resultante_id',
        'asunto',
        'detalle',
        'fecha_actuacion',
        'visible_externo',
    ];

    protected $casts = [
        'fecha_actuacion' => 'datetime',
        'visible_externo' => 'boolean',
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

    public function derivacion(): BelongsTo
    {
        return $this->belongsTo(
            Derivacion::class,
            'derivacion_id'
        );
    }

    public function tipoActuacion(): BelongsTo
    {
        return $this->belongsTo(
            TipoActuacion::class,
            'tipo_actuacion_id'
        );
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_id'
        );
    }

    public function realizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'realizado_por'
        );
    }

    public function estadoResultante(): BelongsTo
    {
        return $this->belongsTo(
            Estado::class,
            'estado_resultante_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeVisiblesExternamente(Builder $query): Builder
    {
        return $query->where('visible_externo', true);
    }

    public function scopeInternas(Builder $query): Builder
    {
        return $query->where('visible_externo', false);
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

    public function scopeDelArea(
        Builder $query,
        int $areaId
    ): Builder {
        return $query->where(
            'area_id',
            $areaId
        );
    }

    public function scopeDelTipo(
        Builder $query,
        int $tipoActuacionId
    ): Builder {
        return $query->where(
            'tipo_actuacion_id',
            $tipoActuacionId
        );
    }

    public function scopeCronologicas(Builder $query): Builder
    {
        return $query->orderBy('fecha_actuacion');
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('fecha_actuacion');
    }

    /**
     * Archivos adjuntos o generados durante la actuación.
     */
    public function archivos(): HasMany
    {
        return $this->hasMany(
            ActuacionArchivo::class,
            'actuacion_id'
        );
    }
}
