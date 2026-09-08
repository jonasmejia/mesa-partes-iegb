<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recepcion extends Model
{
    protected $table = 'recepciones';

    protected $fillable = [
        'derivacion_id',
        'recibido_por',
        'resultado',
        'observacion',
        'fecha_recepcion',
    ];

    protected $casts = [
        'fecha_recepcion' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Derivación que fue recepcionada.
     */
    public function derivacion(): BelongsTo
    {
        return $this->belongsTo(
            Derivacion::class,
            'derivacion_id'
        );
    }

    /**
     * Usuario que realizó la recepción.
     */
    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recibido_por'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Filtra recepciones por resultado.
     */
    public function scopeDelResultado(
        Builder $query,
        string $resultado
    ): Builder {
        return $query->where('resultado', $resultado);
    }

    /**
     * Recepciones registradas como recibidas.
     */
    public function scopeRecibidas(Builder $query): Builder
    {
        return $query->where('resultado', 'RECIBIDO');
    }

    /**
     * Recepciones más recientes primero.
     */
    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('fecha_recepcion');
    }
}