<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DerivacionArchivo extends Model
{
    protected $table = 'derivacion_archivos';

    protected $fillable = [
        'derivacion_id',
        'nombre_original',
        'nombre_guardado',
        'ruta',
        'mime_type',
        'tamano_bytes',
        'hash_sha256',
        'subido_por',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Derivación a la que pertenece el archivo.
     */
    public function derivacion(): BelongsTo
    {
        return $this->belongsTo(
            Derivacion::class,
            'derivacion_id'
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
}