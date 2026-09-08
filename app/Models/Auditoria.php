<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Auditoria extends Model
{
    protected $table = 'auditorias';

    protected $fillable = [
        'user_id',
        'evento',
        'tabla',
        'registro_id',
        'valores_anteriores',
        'valores_nuevos',
        'ip',
        'user_agent',
        'fecha',
    ];

    // protected $casts = [
    //     'registro_id' => 'integer',
    //     'valores_anteriores' => 'array',
    //     'valores_nuevos' => 'array',
    //     'fecha' => 'datetime',
    // ];
     protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'fecha' => 'datetime',
    ];

    


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Usuario que originó el evento auditado.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDelEvento(
        Builder $query,
        string $evento
    ): Builder {
        return $query->where('evento', $evento);
    }

    public function scopeDeTabla(
        Builder $query,
        string $tabla
    ): Builder {
        return $query->where('tabla', $tabla);
    }

    public function scopeDelRegistro(
        Builder $query,
        string $tabla,
        int $registroId
    ): Builder {
        return $query
            ->where('tabla', $tabla)
            ->where('registro_id', $registroId);
    }

    public function scopeDelUsuario(
        Builder $query,
        int $userId
    ): Builder {
        return $query->where('user_id', $userId);
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('fecha');
    }
}
