<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpedienteDocumento extends Model
{
    protected $table = 'expediente_documentos';

    protected $fillable = [
        'expediente_id',
        'documento_id',
        'relacion',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Expediente al que pertenece la asociación.
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(
            Expediente::class,
            'expediente_id'
        );
    }

    /**
     * Documento asociado al expediente.
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(
            Documento::class,
            'documento_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Documentos principales del expediente.
     */
    public function scopePrincipales(Builder $query): Builder
    {
        return $query->where('relacion', 'PRINCIPAL');
    }

    /**
     * Filtra por tipo de relación.
     */
    public function scopeDeRelacion(
        Builder $query,
        string $relacion
    ): Builder {
        return $query->where('relacion', $relacion);
    }

    /**
     * Ordena los documentos según su posición dentro del expediente.
     */
    public function scopePorOrden(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}