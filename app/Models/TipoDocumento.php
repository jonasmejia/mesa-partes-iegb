<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDocumento extends Model
{
    protected $table = 'tipos_documento';

    protected $fillable = [
        'codigo',
        'nombre',
        'ambito',
        'descripcion',
        'requiere_numero',
        'activo',
    ];

    protected $casts = [
        'requiere_numero' => 'boolean',
        'activo' => 'boolean',
    ];

    public function correlativos(): HasMany
    {
        return $this->hasMany(
            Correlativo::class,
            'tipo_documento_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Solo tipos de documento activos.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Tipos aplicables a documentos internos.
     */
    public function scopeInternos(Builder $query): Builder
    {
        return $query->whereIn('ambito', ['INTERNO', 'AMBOS']);
    }

    /**
     * Tipos aplicables a documentos externos.
     */
    public function scopeExternos(Builder $query): Builder
    {
        return $query->whereIn('ambito', ['EXTERNO', 'AMBOS']);
    }

    /**
     * Solo tipos que requieren número de documento.
     */
    public function scopeQueRequierenNumero(Builder $query): Builder
    {
        return $query->where('requiere_numero', true);
    }
}
