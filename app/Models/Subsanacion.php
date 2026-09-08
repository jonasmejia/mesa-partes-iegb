<?php

namespace App\Models;

use App\Enums\EstadoRevisionSubsanacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subsanacion extends Model
{
    protected $table = 'subsanaciones';

    protected $fillable = [
        'observacion_id',
        'documento_id',
        'registrado_por',
        'detalle',
        'fecha_subsanacion',
        'estado_revision',
        'revisado_por',
        'fecha_revision',
        'observacion_revision',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'fecha_subsanacion' => 'datetime',
            'fecha_revision' => 'datetime',
            'estado_revision' => EstadoRevisionSubsanacion::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Observación que originó la subsanación.
     */
    public function observacion(): BelongsTo
    {
        return $this->belongsTo(
            Observacion::class,
            'observacion_id'
        );
    }

    /**
     * Documento asociado a la subsanación.
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(
            Documento::class,
            'documento_id'
        );
    }

    /**
     * Usuario que registró la subsanación.
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por'
        );
    }

    /**
     * Usuario que revisó la subsanación.
     */
    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revisado_por'
        );
    }

    

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where(
            'estado_revision',
            EstadoRevisionSubsanacion::PENDIENTE->value
        );
    }

    public function scopeAceptadas(Builder $query): Builder
    {
        return $query->where(
            'estado_revision',
            EstadoRevisionSubsanacion::ACEPTADA->value
        );
    }

    public function scopeRechazadas(Builder $query): Builder
    {
        return $query->where(
            'estado_revision',
            EstadoRevisionSubsanacion::RECHAZADA->value
        );
    }

    public function scopeRevisadas(Builder $query): Builder
    {
        return $query->whereIn(
            'estado_revision',
            [
                EstadoRevisionSubsanacion::ACEPTADA->value,
                EstadoRevisionSubsanacion::RECHAZADA->value,
            ]
        );
    }

    public function scopeSinRevisar(Builder $query): Builder
    {
        return $query->where(
            'estado_revision',
            EstadoRevisionSubsanacion::PENDIENTE->value
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

    public function scopeDeObservacion(
        Builder $query,
        int $observacionId
    ): Builder {
        return $query->where(
            'observacion_id',
            $observacionId
        );
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('fecha_subsanacion');
    }
}
