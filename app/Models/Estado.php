<?php

namespace App\Models;

use App\Enums\EstadoAmbito;
use App\Enums\EstadoCodigo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estado extends Model
{
    protected $table = 'estados';

    protected $fillable = [
        'codigo',
        'nombre',
        'ambito',
        'descripcion',
        'orden',
        'es_final',
        'activo',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'ambito' => EstadoAmbito::class,
            'orden' => 'integer',
            'es_final' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Documentos que actualmente tienen este estado.
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(
            Documento::class,
            'estado_id'
        );
    }

    /**
     * Derivaciones que actualmente tienen este estado.
     */
    public function derivaciones(): HasMany
    {
        return $this->hasMany(
            Derivacion::class,
            'estado_id'
        );
    }

    /**
     * Expedientes que actualmente tienen este estado.
     */
    public function expedientes(): HasMany
    {
        return $this->hasMany(
            Expediente::class,
            'estado_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Solo estados activos.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where(
            'activo',
            true
        );
    }

    /**
     * Filtra estados por ámbito.
     */
    public function scopeDelAmbito(
        Builder $query,
        EstadoAmbito $ambito
    ): Builder {
        return $query->where(
            'ambito',
            $ambito->value
        );
    }

    /**
     * Solo estados finales.
     */
    public function scopeFinales(Builder $query): Builder
    {
        return $query->where(
            'es_final',
            true
        );
    }

    /**
     * Solo estados no finales.
     */
    public function scopeNoFinales(Builder $query): Builder
    {
        return $query->where(
            'es_final',
            false
        );
    }

    /**
     * Ordena los estados según el campo orden.
     */
    public function scopePorOrden(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos auxiliares
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene un estado a partir de su código.
     */
    public static function porCodigo(EstadoCodigo $codigo): self
    {
        return static::query()
            ->where('codigo', $codigo->value)
            ->firstOrFail();
    }
}