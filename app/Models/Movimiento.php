<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    protected $table = 'movimientos';

    protected $fillable = [
        'documento_id',
        'user_id',
        'area_id',
        'tipo_movimiento',
        'estado_anterior_id',
        'estado_nuevo_id',
        'entidad_tipo',
        'entidad_id',
        'detalle',
        'fecha_movimiento',
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
        'entidad_id' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Documento al que pertenece el movimiento.
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(
            Documento::class,
            'documento_id'
        );
    }

    /**
     * Usuario que ejecutó la acción que originó el movimiento.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * Área donde ocurrió o desde donde se registró el movimiento.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_id'
        );
    }

    /**
     * Estado anterior del documento.
     */
    public function estadoAnterior(): BelongsTo
    {
        return $this->belongsTo(
            Estado::class,
            'estado_anterior_id'
        );
    }

    /**
     * Estado posterior al movimiento.
     */
    public function estadoNuevo(): BelongsTo
    {
        return $this->belongsTo(
            Estado::class,
            'estado_nuevo_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Movimientos de un tipo determinado.
     */
    public function scopeDelTipo(
        Builder $query,
        string $tipo
    ): Builder {
        return $query->where('tipo_movimiento', $tipo);
    }

    /**
     * Movimientos de un documento específico.
     */
    public function scopeDelDocumento(
        Builder $query,
        int $documentoId
    ): Builder {
        return $query->where(
            'documento_id',
            $documentoId
        );
    }

    /**
     * Orden cronológico.
     */
    public function scopeCronologicos(Builder $query): Builder
    {
        return $query->orderBy('fecha_movimiento');
    }

    /**
     * Movimientos más recientes primero.
     */
    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('fecha_movimiento');
    }

    /**
     * Movimientos que implicaron un cambio de estado.
     */
    public function scopeConCambioEstado(Builder $query): Builder
    {
        return $query
            ->whereNotNull('estado_nuevo_id');
    }
}