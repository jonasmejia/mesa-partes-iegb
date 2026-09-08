<?php

namespace App\Services\Derivaciones;

use App\Models\Derivacion;
use App\Models\Documento;
use App\Models\Estado;
use App\Models\UserCargo;
use App\Services\Archivos\ArchivoDerivacionService;
use App\Services\Auditoria\AuditoriaService;
use App\Services\Movimientos\MovimientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DerivacionService
{
    public function __construct(
        private readonly ArchivoDerivacionService $archivoDerivacionService,
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {}

    public function derivar(
        Documento $documento,
        array $datos
    ): Derivacion {
        $rutasGuardadas = [];

        try {
            return DB::transaction(function () use (
                $documento,
                $datos,
                &$rutasGuardadas
            ) {
                /*
                |--------------------------------------------------------------------------
                | 1. Bloquear documento
                |--------------------------------------------------------------------------
                */

                $documento = Documento::query()
                    ->whereKey($documento->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | Validar que el documento pueda derivarse
                |--------------------------------------------------------------------------
                */
                $this->validarEstadoDocumento(
                    $documento
                );
                /*
                |--------------------------------------------------------------------------
                | Evitar derivaciones simultáneas
                |--------------------------------------------------------------------------
                */

                $this->validarSinDerivacionAbierta(
                    $documento
                );
                /*
                |--------------------------------------------------------------------------
                | 2. Validar área origen
                |--------------------------------------------------------------------------
                */

                $this->validarAreaOrigen(
                    documento: $documento,
                    areaOrigenId: $datos['area_origen_id'],
                );

                /*
                |--------------------------------------------------------------------------
                | 3. Validar usuario derivador
                |--------------------------------------------------------------------------
                */

                $this->validarUsuarioEnArea(
                    userId: $datos['derivado_por'],
                    areaId: $datos['area_origen_id'],
                    campo: 'derivado_por',
                );

                /*
                |--------------------------------------------------------------------------
                | 4. Validar destino
                |--------------------------------------------------------------------------
                */

                $this->validarDestino(
                    areaOrigenId: $datos['area_origen_id'],
                    areaDestinoId: $datos['area_destino_id'],
                );

                /*
                |--------------------------------------------------------------------------
                | 5. Validar responsable destino, si existe
                |--------------------------------------------------------------------------
                */

                if (! empty($datos['responsable_destino_id'])) {
                    $this->validarUsuarioEnArea(
                        userId: $datos['responsable_destino_id'],
                        areaId: $datos['area_destino_id'],
                        campo: 'responsable_destino_id',
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 6. Obtener estados
                |--------------------------------------------------------------------------
                */

                $estadoDerivacion =
                    $this->obtenerEstadoDerivacionInicial();

                $estadoDocumento =
                    $this->obtenerEstadoDocumentoEnTramite();

                $estadoAnteriorDocumentoId =
                    $documento->estado_id;

                $areaAnteriorId =
                    $documento->area_actual_id;

                /*
                |--------------------------------------------------------------------------
                | 7. Crear derivación
                |--------------------------------------------------------------------------
                */

                $derivacion = Derivacion::create([
                    'documento_id' =>
                    $documento->id,

                    'area_origen_id' =>
                    $datos['area_origen_id'],

                    'area_destino_id' =>
                    $datos['area_destino_id'],

                    'derivado_por' =>
                    $datos['derivado_por'],

                    'responsable_destino_id' =>
                    $datos['responsable_destino_id'] ?? null,

                    'estado_id' =>
                    $estadoDerivacion->id,

                    'indicacion' =>
                    $datos['indicacion'] ?? null,

                    'fecha_derivacion' =>
                    now(),

                    'fecha_limite' =>
                    $datos['fecha_limite'] ?? null,

                    'fecha_recepcion' =>
                    null,

                    'fecha_atencion' =>
                    null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | 8. Guardar archivos de derivación
                |--------------------------------------------------------------------------
                */

                if (! empty($datos['archivos'])) {
                    $archivosGuardados =
                        $this->archivoDerivacionService
                        ->guardarArchivos(
                            derivacion: $derivacion,
                            archivos: $datos['archivos'],
                            subidoPor: $datos['derivado_por'],
                        );

                    foreach ($archivosGuardados as $archivo) {
                        $rutasGuardadas[] = $archivo->ruta;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | 9. Actualizar documento
                |--------------------------------------------------------------------------
                */

                $documento->update([
                    'estado_id' =>
                    $estadoDocumento->id,

                    'area_actual_id' =>
                    $datos['area_destino_id'],
                ]);

                /*
                |--------------------------------------------------------------------------
                | 10. Registrar movimiento
                |--------------------------------------------------------------------------
                */

                $this->movimientoService
                    ->registrarDerivacion(
                        documento: $documento,
                        derivacion: $derivacion,
                        userId: $datos['derivado_por'],
                        areaOrigenId: $areaAnteriorId,
                        areaDestinoId: $datos['area_destino_id'],
                        estadoAnteriorId: $estadoAnteriorDocumentoId,
                        estadoNuevoId: $estadoDocumento->id,
                    );

                /*
                |--------------------------------------------------------------------------
                | 11. Auditoría
                |--------------------------------------------------------------------------
                */

                $this->auditoriaService
                    ->registrarDerivacion(
                        documento: $documento,
                        derivacion: $derivacion,
                        estadoAnteriorDocumentoId: $estadoAnteriorDocumentoId,
                        areaAnteriorId: $areaAnteriorId,
                        userId: $datos['derivado_por'],
                        ip: $datos['ip'] ?? null,
                        userAgent: $datos['user_agent'] ?? null,
                    );

                return $derivacion->fresh();
            }, 3);
        } catch (Throwable $e) {
            $this->archivoDerivacionService
                ->eliminarArchivosFisicos(
                    $rutasGuardadas
                );

            throw $e;
        }
    }

    private function validarAreaOrigen(
        Documento $documento,
        int $areaOrigenId
    ): void {
        if (
            (int) $documento->area_actual_id
            !==
            (int) $areaOrigenId
        ) {
            throw ValidationException::withMessages([
                'area_origen_id' =>
                'El documento ya no se encuentra en el área de origen indicada.',
            ]);
        }
    }

    private function validarDestino(
        int $areaOrigenId,
        int $areaDestinoId
    ): void {
        if ($areaOrigenId === $areaDestinoId) {
            throw ValidationException::withMessages([
                'area_destino_id' =>
                'El área de destino debe ser diferente al área de origen.',
            ]);
        }
    }

    private function validarUsuarioEnArea(
        int $userId,
        int $areaId,
        string $campo
    ): void {
        $existe = UserCargo::query()
            ->where('user_id', $userId)
            ->where('area_id', $areaId)
            ->where('activo', true)
            ->whereDate(
                'fecha_inicio',
                '<=',
                now()->toDateString()
            )
            ->where(function ($query) {
                $query
                    ->whereNull('fecha_fin')
                    ->orWhereDate(
                        'fecha_fin',
                        '>=',
                        now()->toDateString()
                    );
            })
            ->exists();

        if (! $existe) {
            throw ValidationException::withMessages([
                $campo =>
                'El usuario no tiene una asignación vigente en el área indicada.',
            ]);
        }
    }

    private function obtenerEstadoDerivacionInicial(): Estado
    {
        $estado = Estado::query()
            ->where('codigo', 'DER_ENVIADA')
            ->where('ambito', 'DERIVACION')
            ->first();

        if (! $estado) {
            throw ValidationException::withMessages([
                'estado_derivacion' =>
                'No se encuentra configurado el estado DER_ENVIADA.',
            ]);
        }

        return $estado;
    }

    private function obtenerEstadoDocumentoEnTramite(): Estado
    {
        $estado = Estado::query()
            ->where('codigo', 'DOC_EN_TRAMITE')
            ->where('ambito', 'DOCUMENTO')
            ->first();

        if (! $estado) {
            throw ValidationException::withMessages([
                'estado_documento' =>
                'No se encuentra configurado el estado DOC_EN_TRAMITE.',
            ]);
        }

        return $estado;
    }

    /**
     * Valida que el documento se encuentre en un estado válido para derivación.
     */
    private function validarEstadoDocumento(
        Documento $documento
    ): void {
        $estado = Estado::query()
            ->whereKey($documento->estado_id)
            ->where('ambito', 'DOCUMENTO')
            ->first();

        if (! $estado) {
            throw ValidationException::withMessages([
                'documento' =>
                'El documento no tiene un estado documental válido.',
            ]);
        }

        $estadosPermitidos = [
            'DOC_REGISTRADO',
            'DOC_EN_TRAMITE',
            'DOC_ADMITIDO',
            'DOC_OBSERVADO',
        ];

        if (! in_array(
            $estado->codigo,
            $estadosPermitidos,
            true
        )) {
            throw ValidationException::withMessages([
                'documento' =>
                sprintf(
                    'El documento no puede derivarse mientras se encuentre en el estado %s.',
                    $estado->codigo
                ),
            ]);
        }
    }

    private function validarSinDerivacionAbierta(
        Documento $documento
    ): void {
        $codigosAbiertos = [
            'DER_PENDIENTE',
            'DER_ENVIADA',
            'DER_RECIBIDA',
        ];

        $estadosAbiertos = Estado::query()
            ->where('ambito', 'DERIVACION')
            ->whereIn('codigo', $codigosAbiertos)
            ->pluck('id');

        if ($estadosAbiertos->count() !== count($codigosAbiertos)) {
            throw ValidationException::withMessages([
                'estado_derivacion' =>
                'La configuración de estados de derivación está incompleta.',
            ]);
        }

        $existeDerivacionAbierta = Derivacion::query()
            ->where('documento_id', $documento->id)
            ->whereIn('estado_id', $estadosAbiertos)
            ->exists();

        if ($existeDerivacionAbierta) {
            throw ValidationException::withMessages([
                'documento' =>
                'El documento ya tiene una derivación abierta.',
            ]);
        }
    }
}
