<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemitenteExterno extends Model
{
    protected $table = 'remitentes_externos';

    protected $fillable = [
        'tipo_persona',
        'tipo_documento_identidad',
        'numero_documento_identidad',
        'nombres',
        'apellidos',
        'razon_social',
        'correo',
        'telefono',
        'direccion',
        'ubigeo',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Documentos externos presentados por este remitente.
     */
    public function documentosExternos(): HasMany
    {
        return $this->hasMany(
            DocumentoExterno::class,
            'remitente_externo_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Solo personas naturales.
     */
    public function scopeNaturales(Builder $query): Builder
    {
        return $query->where('tipo_persona', 'NATURAL');
    }

    /**
     * Solo personas jurídicas.
     */
    public function scopeJuridicas(Builder $query): Builder
    {
        return $query->where('tipo_persona', 'JURIDICA');
    }

    /**
     * Busca por número de documento de identidad.
     */
    public function scopePorDocumento(
        Builder $query,
        string $numeroDocumento
    ): Builder {
        return $query->where(
            'numero_documento_identidad',
            $numeroDocumento
        );
    }

    /**
     * Busca por correo electrónico.
     */
    public function scopePorCorreo(
        Builder $query,
        string $correo
    ): Builder {
        return $query->where('correo', $correo);
    }

    /**
     * Notificaciones dirigidas al remitente externo.
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(
            Notificacion::class,
            'remitente_externo_id'
        );
    }
}
