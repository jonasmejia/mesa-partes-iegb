<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoArchivo extends Model
{
    protected $table = 'documento_archivos';

    protected $fillable = [
        'documento_id',
        'tipo',
        'nombre_original',
        'nombre_guardado',
        'ruta',
        'mime_type',
        'tamano_bytes',
        'hash_sha256',
        'subido_por',
        'es_principal',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'es_principal' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Documento al que pertenece el archivo.
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(
            Documento::class,
            'documento_id'
        );
    }

    /**
     * Usuario que subió el archivo.
     */
    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'subido_por'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Solo archivos principales.
     */
    public function scopePrincipales(Builder $query): Builder
    {
        return $query->where('es_principal', true);
    }

    /**
     * Filtra archivos por tipo.
     */
    public function scopeDelTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }
}