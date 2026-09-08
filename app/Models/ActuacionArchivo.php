<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActuacionArchivo extends Model
{
    protected $table = 'actuacion_archivos';

    protected $fillable = [
        'actuacion_id',
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
     * Actuación a la que pertenece el archivo.
     */
    public function actuacion(): BelongsTo
    {
        return $this->belongsTo(
            Actuacion::class,
            'actuacion_id'
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