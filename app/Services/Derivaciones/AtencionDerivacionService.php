<?php

namespace App\Services\Derivaciones;

use App\Models\Derivacion;
use App\Models\Documento;
use App\Models\Estado;
use App\Models\UserCargo;
use App\Services\Auditoria\AuditoriaService;
use App\Services\Movimientos\MovimientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AtencionDerivacionService
{
    private const RESULTADO_CONTINUAR = 'CONTINUAR';

    private const RESULTADO_DERIVAR = 'DERIVAR';

    private const RESULTADO_FINALIZAR = 'FINALIZAR';


    public function __construct(
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }


    public function atender(
        Derivacion $derivacion,
        int $atendidoPor,
        string $resultado = self::RESULTADO_CONTINUAR,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Derivacion {
        return DB::transaction(function () use (
            $derivacion,
            $atendidoPor,
            $resultado,
            $ip,
            $userAgent
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. Bloquear derivación
            |--------------------------------------------------------------------------
            */

            $derivacion = Derivacion::query()
                ->whereKey($derivacion->id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 2. Bloquear documento
            |--------------------------------------------------------------------------
            */

            $documento = Documento::query()
                ->whereKey($derivacion->documento_id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 3. Validar resultado solicitado
            |--------------------------------------------------------------------------
            */

            $this->validarResultado(
                $resultado
            );


            /*
            |--------------------------------------------------------------------------
            | 4. Obtener estados
            |--------------------------------------------------------------------------
            */

            $estadoRecibida =
                $this->obtenerEstadoDerivacion(
                    'DER_RECIBIDA'
                );

            $estadoAtendida =
                $this->obtenerEstadoDerivacion(
                    'DER_ATENDIDA'
                );


            /*
            |--------------------------------------------------------------------------
            | 5. Validar estado actual de derivación
            |--------------------------------------------------------------------------
            */

            $this->validarEstadoActual(
                derivacion: $derivacion,
                estadoRecibidaId: $estadoRecibida->id,
            );


            /*
            |--------------------------------------------------------------------------
            | 6. Validar fecha de recepción
            |--------------------------------------------------------------------------
            */

            if ($derivacion->fecha_recepcion === null) {
                throw ValidationException::withMessages([
                    'derivacion' =>
                        'La derivación debe ser recibida antes de poder ser atendida.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 7. Evitar atención duplicada
            |--------------------------------------------------------------------------
            */

            if ($derivacion->fecha_atencion !== null) {
                throw ValidationException::withMessages([
                    'derivacion' =>
                        'La derivación ya fue atendida anteriormente.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 8. Validar usuario que atiende
            |--------------------------------------------------------------------------
            */

            $this->validarUsuarioEnAreaDestino(
                userId: $atendidoPor,
                areaDestinoId: $derivacion->area_destino_id,
            );


            /*
            |--------------------------------------------------------------------------
            | 9. Validar que el documento siga en el área destino
            |--------------------------------------------------------------------------
            */

            $this->validarDocumentoEnAreaDestino(
                documento: $documento,
                areaDestinoId: $derivacion->area_destino_id,
            );


            /*
            |--------------------------------------------------------------------------
            | 10. Guardar estados anteriores
            |--------------------------------------------------------------------------
            */

            $estadoAnteriorDerivacionId =
                $derivacion->estado_id;

            $estadoAnteriorDocumentoId =
                $documento->estado_id;


            /*
            |--------------------------------------------------------------------------
            | 11. Marcar derivación como atendida
            |--------------------------------------------------------------------------
            */

            $derivacion->update([
                'estado_id' =>
                    $estadoAtendida->id,

                'fecha_atencion' =>
                    now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | 12. Determinar qué ocurre con el documento
            |--------------------------------------------------------------------------
            */

            $estadoNuevoDocumentoId =
                $estadoAnteriorDocumentoId;

            if (
                $resultado === self::RESULTADO_FINALIZAR
            ) {
                $estadoDocumentoAtendido =
                    $this->obtenerEstadoDocumento(
                        'DOC_ATENDIDO'
                    );

                $documento->update([
                    'estado_id' =>
                        $estadoDocumentoAtendido->id,
                ]);

                $estadoNuevoDocumentoId =
                    $estadoDocumentoAtendido->id;
            }


            /*
            |--------------------------------------------------------------------------
            | 13. Registrar movimiento de atención
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarAtencionDerivacion(
                    documento: $documento,
                    derivacion: $derivacion,
                    userId: $atendidoPor,
                    areaId: $derivacion->area_destino_id,
                    estadoAnteriorId:
                        $estadoAnteriorDerivacionId,
                    estadoNuevoId:
                        $estadoAtendida->id,
                    resultado:
                        $resultado,
                );


            /*
            |--------------------------------------------------------------------------
            | 14. Si finaliza documento, registrar cambio documental
            |--------------------------------------------------------------------------
            */

            if (
                $estadoAnteriorDocumentoId
                !==
                $estadoNuevoDocumentoId
            ) {
                $this->movimientoService
                    ->registrarDocumentoAtendido(
                        documento: $documento,
                        userId: $atendidoPor,
                        areaId: $derivacion->area_destino_id,
                        estadoAnteriorId:
                            $estadoAnteriorDocumentoId,
                        estadoNuevoId:
                            $estadoNuevoDocumentoId,
                        derivacionId:
                            $derivacion->id,
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | 15. Auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarAtencionDerivacion(
                    documento: $documento,
                    derivacion: $derivacion,
                    resultado: $resultado,
                    estadoAnteriorDerivacionId:
                        $estadoAnteriorDerivacionId,
                    estadoAnteriorDocumentoId:
                        $estadoAnteriorDocumentoId,
                    userId: $atendidoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $derivacion->fresh();

        }, 3);
    }


    private function validarResultado(
        string $resultado
    ): void {
        if (! in_array(
            $resultado,
            [
                self::RESULTADO_CONTINUAR,
                self::RESULTADO_DERIVAR,
                self::RESULTADO_FINALIZAR,
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'resultado' =>
                    'El resultado de atención indicado no es válido.',
            ]);
        }
    }


    private function obtenerEstadoDerivacion(
        string $codigo
    ): Estado {
        $estado = Estado::query()
            ->where('codigo', $codigo)
            ->where('ambito', 'DERIVACION')
            ->first();

        if (! $estado) {
            throw ValidationException::withMessages([
                'estado_derivacion' =>
                    "No se encuentra configurado el estado {$codigo}.",
            ]);
        }

        return $estado;
    }


    private function obtenerEstadoDocumento(
        string $codigo
    ): Estado {
        $estado = Estado::query()
            ->where('codigo', $codigo)
            ->where('ambito', 'DOCUMENTO')
            ->first();

        if (! $estado) {
            throw ValidationException::withMessages([
                'estado_documento' =>
                    "No se encuentra configurado el estado {$codigo}.",
            ]);
        }

        return $estado;
    }


    private function validarEstadoActual(
        Derivacion $derivacion,
        int $estadoRecibidaId
    ): void {
        if (
            (int) $derivacion->estado_id
            !==
            (int) $estadoRecibidaId
        ) {
            $estadoActual = Estado::find(
                $derivacion->estado_id
            );

            throw ValidationException::withMessages([
                'derivacion' =>
                    sprintf(
                        'La derivación no puede ser atendida porque actualmente se encuentra en estado %s.',
                        $estadoActual?->codigo ?? 'DESCONOCIDO'
                    ),
            ]);
        }
    }


    private function validarUsuarioEnAreaDestino(
        int $userId,
        int $areaDestinoId
    ): void {
        $asignacion = UserCargo::query()
            ->where('user_id', $userId)
            ->where('area_id', $areaDestinoId)
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

        if (! $asignacion) {
            throw ValidationException::withMessages([
                'atendido_por' =>
                    'El usuario no pertenece actualmente al área destino de la derivación.',
            ]);
        }
    }


    private function validarDocumentoEnAreaDestino(
        Documento $documento,
        int $areaDestinoId
    ): void {
        if (
            (int) $documento->area_actual_id
            !==
            (int) $areaDestinoId
        ) {
            throw ValidationException::withMessages([
                'documento' =>
                    'El documento ya no se encuentra en el área responsable de esta derivación.',
            ]);
        }
    }
}