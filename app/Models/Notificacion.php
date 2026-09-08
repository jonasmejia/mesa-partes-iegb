<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'documento_id',
        'user_id',
        'remitente_externo_id',
        'canal',
        'destino',
        'asunto',
        'mensaje',
        'estado_envio',
        'intentos',
        'fecha_programada',
        'fecha_envio',
        'error_envio',
    ];

    protected $casts = [
        'intentos' => 'integer',
        'fecha_programada' => 'datetime',
        'fecha_envio' => 'datetime',
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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function remitenteExterno(): BelongsTo
    {
        return $this->belongsTo(
            RemitenteExterno::class,
            'remitente_externo_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado_envio', 'PENDIENTE');
    }

    public function scopeDelEstado(
        Builder $query,
        string $estado
    ): Builder {
        return $query->where('estado_envio', $estado);
    }

    public function scopeDelCanal(
        Builder $query,
        string $canal
    ): Builder {
        return $query->where('canal', $canal);
    }

    public function scopeProgramadas(Builder $query): Builder
    {
        return $query->whereNotNull('fecha_programada');
    }

    public function scopeEnviadas(Builder $query): Builder
    {
        return $query->whereNotNull('fecha_envio');
    }

    public function scopeNoEnviadas(Builder $query): Builder
    {
        return $query->whereNull('fecha_envio');
    }

    public function scopeConError(Builder $query): Builder
    {
        return $query->whereNotNull('error_envio');
    }

    public function scopeListasParaEnviar(Builder $query): Builder
    {
        return $query
            ->where('estado_envio', 'PENDIENTE')
            ->where(function ($query) {
                $query
                    ->whereNull('fecha_programada')
                    ->orWhere('fecha_programada', '<=', now());
            });
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->latest();
    }
}