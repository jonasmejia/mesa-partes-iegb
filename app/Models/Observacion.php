<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Enums\EstadoRevisionSubsanacion;


class Observacion extends Model
{
    protected $table = 'observaciones';

    protected $fillable = [
        'documento_id',
        'area_id',
        'registrado_por',
        'tipo',
        'detalle',
        'requiere_subsanacion',
        'fecha_observacion',
        'fecha_subsanacion_limite',
    ];

    protected $casts = [
        'requiere_subsanacion' => 'boolean',
        'fecha_observacion' => 'datetime',
        'fecha_subsanacion_limite' => 'datetime',
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

    public function area(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_id'
        );
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeQueRequierenSubsanacion(Builder $query): Builder
    {
        return $query->where('requiere_subsanacion', true);
    }

    public function scopeSinSubsanacionRequerida(Builder $query): Builder
    {
        return $query->where('requiere_subsanacion', false);
    }

    public function scopeDelTipo(
        Builder $query,
        string $tipo
    ): Builder {
        return $query->where('tipo', $tipo);
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

    public function scopeVencidasParaSubsanar(Builder $query): Builder
    {
        return $query
            ->where('requiere_subsanacion', true)
            ->whereNotNull('fecha_subsanacion_limite')
            ->where('fecha_subsanacion_limite', '<', now())
            ->whereDoesntHave(
                'subsanaciones',
                function (Builder $query) {
                    $query->where(
                        'estado_revision',
                        EstadoRevisionSubsanacion::ACEPTADA->value
                    );
                }
            );
    }

    public function scopeSubsanadas(Builder $query): Builder
    {
        return $query->whereHas(
            'subsanaciones',
            fn(Builder $query) =>
            $query->where(
                'estado_revision',
                EstadoRevisionSubsanacion::ACEPTADA->value
            )
        );
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('fecha_observacion');
    }

    /**
     * Subsanaciones presentadas para esta observación.
     */
    public function subsanaciones(): HasMany
    {
        return $this->hasMany(
            Subsanacion::class,
            'observacion_id'
        );
    }
}
