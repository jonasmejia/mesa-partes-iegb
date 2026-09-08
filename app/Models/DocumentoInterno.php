<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoInterno extends Model
{
    protected $table = 'documentos_internos';

    protected $fillable = [
        'documento_id',
        'area_origen_id',
        'emisor_user_id',
        'requiere_firma',
        'fecha_emision',
    ];

    protected $casts = [
        'requiere_firma' => 'boolean',
        'fecha_emision' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Documento principal al que pertenece este registro interno.
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(
            Documento::class,
            'documento_id'
        );
    }

    /**
     * Área institucional que origina/emite el documento.
     */
    public function areaOrigen(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_origen_id'
        );
    }

    /**
     * Usuario institucional que emite el documento.
     */
    public function emisor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'emisor_user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Documentos internos que requieren firma.
     */
    public function scopeQueRequierenFirma(Builder $query): Builder
    {
        return $query->where('requiere_firma', true);
    }

    /**
     * Documentos internos que no requieren firma.
     */
    public function scopeQueNoRequierenFirma(Builder $query): Builder
    {
        return $query->where('requiere_firma', false);
    }

    /**
     * Documentos internos que ya tienen fecha de emisión.
     */
    public function scopeEmitidos(Builder $query): Builder
    {
        return $query->whereNotNull('fecha_emision');
    }

    /**
     * Documentos internos todavía sin fecha de emisión.
     */
    public function scopePendientesEmision(Builder $query): Builder
    {
        return $query->whereNull('fecha_emision');
    }
}