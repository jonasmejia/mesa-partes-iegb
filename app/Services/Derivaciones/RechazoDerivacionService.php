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

class RechazoDerivacionService
{
    public function __construct(
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }

    public function rechazar(
        Derivacion $derivacion,
        int $rechazadoPor,
        string $motivo,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Derivacion {
        return DB::transaction(function () use (
            $derivacion,
            $rechazadoPor,
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

            $estadoEnviada =
                $this->obtenerEstadoDerivacion(
                    'DER_ENVIADA'
                );

            $estadoRechazada =
                $this->obtenerEstadoDerivacion(
                    'DER_RECHAZADA'
                );


            /*
            |--------------------------------------------------------------------------
            | 5. Validar estado actual
            |--------------------------------------------------------------------------
            */

            $this->validarEstadoActual(
                derivacion: $derivacion,
                estadoEnviadaId: $estadoEnviada->id,
            );


            /*
            |--------------------------------------------------------------------------
            | 6. Validar que no haya sido recibida
            |--------------------------------------------------------------------------
            */

            if ($derivacion->fecha_recepcion !== null) {
                throw ValidationException::withMessages([
                    'derivacion' =>
                        'La derivación ya fue recibida y no puede ser rechazada.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 7. Validar usuario en área destino
            |--------------------------------------------------------------------------
            */

            $this->validarUsuarioEnAreaDestino(
                userId: $rechazadoPor,
                areaDestinoId: $derivacion->area_destino_id,
            );


            /*
            |--------------------------------------------------------------------------
            | 8. Validar ubicación del documento
            |--------------------------------------------------------------------------
            */

            $this->validarDocumentoEnAreaDestino(
                documento: $documento,
                areaDestinoId: $derivacion->area_destino_id,
            );


            /*
            |--------------------------------------------------------------------------
            | 9. Guardar valores anteriores
            |--------------------------------------------------------------------------
            */

            $estadoAnteriorDerivacionId =
                $derivacion->estado_id;

            $areaAnteriorDocumentoId =
                $documento->area_actual_id;


            /*
            |--------------------------------------------------------------------------
            | 10. Rechazar derivación
            |--------------------------------------------------------------------------
            */

            $derivacion->update([
                'estado_id' =>
                    $estadoRechazada->id,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 11. Devolver documento al área origen
            |--------------------------------------------------------------------------
            */

            $documento->update([
                'area_actual_id' =>
                    $derivacion->area_origen_id,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 12. Registrar movimiento
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarRechazoDerivacion(
                    documento: $documento,
                    derivacion: $derivacion,
                    userId: $rechazadoPor,
                    areaId: $derivacion->area_destino_id,
                    estadoAnteriorId:
                        $estadoAnteriorDerivacionId,
                    estadoNuevoId:
                        $estadoRechazada->id,
                    motivo: $motivo,
                );


            /*
            |--------------------------------------------------------------------------
            | 13. Registrar auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarRechazoDerivacion(
                    documento: $documento,
                    derivacion: $derivacion,
                    estadoAnteriorDerivacionId:
                        $estadoAnteriorDerivacionId,
                    areaAnteriorDocumentoId:
                        $areaAnteriorDocumentoId,
                    motivo: $motivo,
                    userId: $rechazadoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $derivacion->fresh();

        }, 3);
    }


    private function validarMotivo(
        string $motivo
    ): void {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw ValidationException::withMessages([
                'motivo' =>
                    'Debe indicar el motivo del rechazo.',
            ]);
        }

        if (mb_strlen($motivo) < 5) {
            throw ValidationException::withMessages([
                'motivo' =>
                    'El motivo del rechazo debe contener al menos 5 caracteres.',
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


    private function validarEstadoActual(
        Derivacion $derivacion,
        int $estadoEnviadaId
    ): void {
        if (
            (int) $derivacion->estado_id
            !==
            (int) $estadoEnviadaId
        ) {
            $estadoActual = Estado::find(
                $derivacion->estado_id
            );

            throw ValidationException::withMessages([
                'derivacion' =>
                    sprintf(
                        'La derivación no puede rechazarse porque actualmente se encuentra en estado %s.',
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
                'rechazado_por' =>
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
                    'El documento ya no se encuentra en el área destino de la derivación.',
            ]);
        }
    }
}