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

class CancelacionDerivacionService
{
    public function __construct(
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }

    public function cancelar(
        Derivacion $derivacion,
        int $canceladoPor,
        string $motivo,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Derivacion {
        return DB::transaction(function () use (
            $derivacion,
            $canceladoPor,
            $motivo,
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
            | 3. Validar motivo
            |--------------------------------------------------------------------------
            */

            $this->validarMotivo($motivo);


            /*
            |--------------------------------------------------------------------------
            | 4. Obtener estados
            |--------------------------------------------------------------------------
            */

            $estadoPendiente =
                $this->obtenerEstadoDerivacion(
                    'DER_PENDIENTE'
                );

            $estadoEnviada =
                $this->obtenerEstadoDerivacion(
                    'DER_ENVIADA'
                );

            $estadoCancelada =
                $this->obtenerEstadoDerivacion(
                    'DER_CANCELADA'
                );


            /*
            |--------------------------------------------------------------------------
            | 5. Validar que pueda cancelarse
            |--------------------------------------------------------------------------
            */

            $this->validarEstadoCancelable(
                derivacion: $derivacion,
                estadoPendienteId: $estadoPendiente->id,
                estadoEnviadaId: $estadoEnviada->id,
            );


            /*
            |--------------------------------------------------------------------------
            | 6. Validar usuario en área origen
            |--------------------------------------------------------------------------
            */

            $this->validarUsuarioEnAreaOrigen(
                userId: $canceladoPor,
                areaOrigenId: $derivacion->area_origen_id,
            );


            /*
            |--------------------------------------------------------------------------
            | 7. Validar ubicación actual del documento
            |--------------------------------------------------------------------------
            */

            $this->validarUbicacionDocumento(
                documento: $documento,
                derivacion: $derivacion,
                estadoPendienteId: $estadoPendiente->id,
                estadoEnviadaId: $estadoEnviada->id,
            );


            /*
            |--------------------------------------------------------------------------
            | 8. Guardar valores anteriores
            |--------------------------------------------------------------------------
            */

            $estadoAnteriorDerivacionId =
                $derivacion->estado_id;

            $areaAnteriorDocumentoId =
                $documento->area_actual_id;


            /*
            |--------------------------------------------------------------------------
            | 9. Cancelar derivación
            |--------------------------------------------------------------------------
            */

            $derivacion->update([
                'estado_id' =>
                    $estadoCancelada->id,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 10. Restaurar documento al área origen
            |--------------------------------------------------------------------------
            */

            if (
                (int) $documento->area_actual_id
                !==
                (int) $derivacion->area_origen_id
            ) {
                $documento->update([
                    'area_actual_id' =>
                        $derivacion->area_origen_id,
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 11. Registrar movimiento
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarCancelacionDerivacion(
                    documento: $documento,
                    derivacion: $derivacion,
                    userId: $canceladoPor,
                    areaId: $derivacion->area_origen_id,
                    estadoAnteriorId:
                        $estadoAnteriorDerivacionId,
                    estadoNuevoId:
                        $estadoCancelada->id,
                    motivo: $motivo,
                );


            /*
            |--------------------------------------------------------------------------
            | 12. Registrar auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarCancelacionDerivacion(
                    documento: $documento,
                    derivacion: $derivacion,
                    estadoAnteriorDerivacionId:
                        $estadoAnteriorDerivacionId,
                    areaAnteriorDocumentoId:
                        $areaAnteriorDocumentoId,
                    motivo: $motivo,
                    userId: $canceladoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $derivacion->fresh();

        }, 3);
    }


    private function validarMotivo(
        string $motivo
    ): void {
        if (trim($motivo) === '') {
            throw ValidationException::withMessages([
                'motivo' =>
                    'Debe indicar el motivo de la cancelación.',
            ]);
        }

        if (mb_strlen(trim($motivo)) < 5) {
            throw ValidationException::withMessages([
                'motivo' =>
                    'El motivo de cancelación debe contener al menos 5 caracteres.',
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


    private function validarEstadoCancelable(
        Derivacion $derivacion,
        int $estadoPendienteId,
        int $estadoEnviadaId,
    ): void {
        $estadosPermitidos = [
            $estadoPendienteId,
            $estadoEnviadaId,
        ];

        if (! in_array(
            (int) $derivacion->estado_id,
            $estadosPermitidos,
            true
        )) {
            $estadoActual = Estado::find(
                $derivacion->estado_id
            );

            throw ValidationException::withMessages([
                'derivacion' =>
                    sprintf(
                        'La derivación no puede cancelarse porque actualmente se encuentra en estado %s.',
                        $estadoActual?->codigo ?? 'DESCONOCIDO'
                    ),
            ]);
        }
    }


    private function validarUsuarioEnAreaOrigen(
        int $userId,
        int $areaOrigenId
    ): void {
        $asignacion = UserCargo::query()
            ->where('user_id', $userId)
            ->where('area_id', $areaOrigenId)
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
                'cancelado_por' =>
                    'El usuario no pertenece actualmente al área origen de la derivación.',
            ]);
        }
    }


    private function validarUbicacionDocumento(
        Documento $documento,
        Derivacion $derivacion,
        int $estadoPendienteId,
        int $estadoEnviadaId,
    ): void {
        if (
            (int) $derivacion->estado_id
            ===
            (int) $estadoPendienteId
        ) {
            if (
                (int) $documento->area_actual_id
                !==
                (int) $derivacion->area_origen_id
            ) {
                throw ValidationException::withMessages([
                    'documento' =>
                        'El documento no se encuentra en el área origen de la derivación pendiente.',
                ]);
            }

            return;
        }

        if (
            (int) $derivacion->estado_id
            ===
            (int) $estadoEnviadaId
        ) {
            if (
                (int) $documento->area_actual_id
                !==
                (int) $derivacion->area_destino_id
            ) {
                throw ValidationException::withMessages([
                    'documento' =>
                        'El documento ya no se encuentra en el área destino de la derivación.',
                ]);
            }
        }
    }
}