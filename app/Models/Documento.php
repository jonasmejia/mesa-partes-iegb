<?php

namespace App\Models;

use App\Enums\EstadoCodigo;
use App\Enums\OrigenDocumento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Documento extends Model
{
    protected $table = 'documentos';

    protected $fillable = [
        'codigo',
        'origen',
        'tipo_documento_id',
        'estado_id',
        'prioridad_id',
        'area_actual_id',
        'registrado_por',
        'numero_documento',
        'anio',
        'sigla',
        'asunto',
        'descripcion',
        'folios',
        'fecha_documento',
        'fecha_registro',
        'fecha_limite',
        'confidencial',
        'requiere_respuesta',
    ];


    protected function casts(): array
    {
        return [
            'origen' => OrigenDocumento::class,
            'anio' => 'integer',
            'folios' => 'integer',
            'fecha_documento' => 'datetime',
            'fecha_registro' => 'datetime',
            'fecha_limite' => 'datetime',
            'confidencial' => 'boolean',
            'requiere_respuesta' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Tipo de documento.
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(
            TipoDocumento::class,
            'tipo_documento_id'
        );
    }

    /**
     * Estado actual del documento.
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(
            Estado::class,
            'estado_id'
        );
    }

    /**
     * Prioridad asignada al documento.
     */
    public function prioridad(): BelongsTo
    {
        return $this->belongsTo(
            Prioridad::class,
            'prioridad_id'
        );
    }

    /**
     * Área donde actualmente se encuentra el documento.
     */
    public function areaActual(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_actual_id'
        );
    }

    /**
     * Usuario interno que realizó el registro.
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por'
        );
    }

    /**
     * Obtiene el código tipado del estado actual.
     */
    public function estadoCodigo(): EstadoCodigo
    {
        return EstadoCodigo::from(
            $this->estado->codigo
        );
    }

    /**
     * Determina si el documento puede cambiar
     * al estado indicado.
     */
    public function puedeCambiarEstadoA(
        EstadoCodigo $nuevoEstado
    ): bool {
        return $this->estadoCodigo()
            ->puedeCambiarA($nuevoEstado);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Solo documentos externos.
     */
    public function scopeExternos(Builder $query): Builder
    {
        return $query->where('origen', OrigenDocumento::EXTERNO->value);
    }

    /**
     * Solo documentos internos.
     */
    public function scopeInternos(Builder $query): Builder
    {
        return $query->where('origen', OrigenDocumento::INTERNO->value);
    }

    /**
     * Documentos confidenciales.
     */
    public function scopeConfidenciales(Builder $query): Builder
    {
        return $query->where('confidencial', true);
    }

    /**
     * Documentos que requieren respuesta.
     */
    public function scopeQueRequierenRespuesta(Builder $query): Builder
    {
        return $query->where('requiere_respuesta', true);
    }

    /**
     * Filtra documentos por año.
     */
    public function scopeDelAnio(Builder $query, int $anio): Builder
    {
        return $query->where('anio', $anio);
    }

    /**
     * Documentos cuya fecha límite ya venció.
     */
    public function scopeVencidos(Builder $query): Builder
    {
        return $query
            ->whereNotNull('fecha_limite')
            ->where('fecha_limite', '<', now());
    }

    /**
     * Documentos con fecha límite pendiente.
     */
    public function scopeConPlazoVigente(Builder $query): Builder
    {
        return $query
            ->whereNotNull('fecha_limite')
            ->where('fecha_limite', '>=', now());
    }

    /**
     * Archivos adjuntos del documento.
     */
    public function archivos(): HasMany
    {
        return $this->hasMany(
            DocumentoArchivo::class,
            'documento_id'
        );
    }

    public function archivosPrincipales(): HasMany
    {
        return $this->hasMany(
            DocumentoArchivo::class,
            'documento_id'
        )->where('es_principal', true);
    }

    /**
     * Datos adicionales cuando el documento es de origen externo.
     */
    public function documentoExterno(): HasOne
    {
        return $this->hasOne(
            DocumentoExterno::class,
            'documento_id'
        );
    }
    /**
     * Datos adicionales cuando el documento es de origen interno.
     */
    public function documentoInterno(): HasOne
    {
        return $this->hasOne(
            DocumentoInterno::class,
            'documento_id'
        );
    }
    /**
     * Registros intermedios expediente-documento.
     */
    public function expedienteDocumentos(): HasMany
    {
        return $this->hasMany(
            ExpedienteDocumento::class,
            'documento_id'
        );
    }

    /**
     * Expedientes a los que pertenece el documento.
     */
    public function expedientes(): BelongsToMany
    {
        return $this->belongsToMany(
            Expediente::class,
            'expediente_documentos',
            'documento_id',
            'expediente_id'
        )
            ->withPivot([
                'id',
                'relacion',
                'orden',
            ])
            ->withTimestamps();
    }

    /**
     * Historial de derivaciones del documento.
     */
    public function derivaciones(): HasMany
    {
        return $this->hasMany(
            Derivacion::class,
            'documento_id'
        );
    }
    //relación muy útil para obtener la última derivación:
    public function ultimaDerivacion()
    {
        return $this->hasOne(
            Derivacion::class,
            'documento_id'
        )->latestOfMany('fecha_derivacion');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(
            Movimiento::class,
            'documento_id'
        );
    }

    public function actuaciones(): HasMany
    {
        return $this->hasMany(
            Actuacion::class,
            'documento_id'
        );
    }
    /**
     * Observaciones registradas sobre el documento.
     */
    public function observaciones(): HasMany
    {
        return $this->hasMany(
            Observacion::class,
            'documento_id'
        );
    }

    /**
     * Subsanaciones asociadas directamente al documento.
     */
    public function subsanaciones(): HasMany
    {
        return $this->hasMany(
            Subsanacion::class,
            'documento_id'
        );
    }
    public function notificaciones(): HasMany
    {
        return $this->hasMany(
            Notificacion::class,
            'documento_id'
        );
    }
    /**
     * Tokens habilitados para el seguimiento externo del documento.
     */
    public function tokensSeguimiento(): HasMany
    {
        return $this->hasMany(
            TokenSeguimiento::class,
            'documento_id'
        );
    }
}
