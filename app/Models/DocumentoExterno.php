<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoExterno extends Model
{
    protected $table = 'documentos_externos';

    protected $fillable = [
        'documento_id',
        'remitente_externo_id',
        'canal_ingreso',
        'codigo_seguimiento',
        'ip_registro',
        'user_agent',
        'fecha_recepcion',
        'recepcionado_por',
        'observacion_recepcion',
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
     * Documento principal al que pertenece este registro externo.
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(
            Documento::class,
            'documento_id'
        );
    }

    /**
     * Remitente externo que presentó el documento.
     *
     * Esta relación quedará operativa cuando creemos RemitenteExterno.
     */
    public function remitenteExterno(): BelongsTo
    {
        return $this->belongsTo(
            RemitenteExterno::class,
            'remitente_externo_id'
        );
    }

    /**
     * Usuario institucional que recepcionó/admitió el documento.
     */
    public function recepcionadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recepcionado_por'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Filtra por canal de ingreso.
     */
    public function scopeDelCanal(Builder $query, string $canal): Builder
    {
        return $query->where('canal_ingreso', $canal);
    }

    /**
     * Documentos externos aún no recepcionados.
     */
    public function scopePendientesRecepcion(Builder $query): Builder
    {
        return $query->whereNull('fecha_recepcion');
    }

    /**
     * Documentos externos ya recepcionados.
     */
    public function scopeRecepcionados(Builder $query): Builder
    {
        return $query->whereNotNull('fecha_recepcion');
    }
}