<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Correlativo extends Model
{
    protected $table = 'correlativos';

    protected $fillable = [
        'tipo',
        'anio',
        'area_id',
        'tipo_documento_id',
        'ultimo_numero',
    ];

    protected $casts = [
        'anio' => 'integer',
        'ultimo_numero' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function area(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_id'
        );
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(
            TipoDocumento::class,
            'tipo_documento_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDelTipo(
        Builder $query,
        string $tipo
    ): Builder {
        return $query->where('tipo', $tipo);
    }

    public function scopeDelAnio(
        Builder $query,
        int $anio
    ): Builder {
        return $query->where('anio', $anio);
    }

    public function scopeDelArea(
        Builder $query,
        int $areaId
    ): Builder {
        return $query->where('area_id', $areaId);
    }

    public function scopeDelTipoDocumento(
        Builder $query,
        int $tipoDocumentoId
    ): Builder {
        return $query->where(
            'tipo_documento_id',
            $tipoDocumentoId
        );
    }

    public function scopeGlobales(Builder $query): Builder
    {
        return $query
            ->whereNull('area_id')
            ->whereNull('tipo_documento_id');
    }
}