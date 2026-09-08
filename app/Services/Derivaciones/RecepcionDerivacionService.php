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

class RecepcionDerivacionService
{
    public function __construct(
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }

    public function recibir(
        Derivacion $derivacion,
        int $recibidoPor,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Derivacion {
        return DB::transaction(function () use (
            $derivacion,
            $recibidoPor,
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
            | 3. Obtener estados
            |--------------------------------------------------------------------------
            */

            $estadoEnviada =
                $this->obtenerEstadoDerivacion('DER_ENVIADA');

            $estadoRecibida =
                $this->obtenerEstadoDerivacion('DER_RECIBIDA');


            /*
            |--------------------------------------------------------------------------
            | 4. Validar estado actual
            |--------------------------------------------------------------------------
            */

            $this->validarEstadoActual(
                derivacion: $derivacion,
                estadoEnviadaId: $estadoEnviada->id,
            );


            /*
            |--------------------------------------------------------------------------
            | 5. Validar que no haya sido recibida anteriormente
            |--------------------------------------------------------------------------
            */

            if ($derivacion->fecha_recepcion !== null) {
                throw ValidationException::withMessages([
                    'derivacion' =>
                        'La derivación ya fue recibida anteriormente.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 6. Validar usuario receptor en área destino
            |--------------------------------------------------------------------------
            */

            $this->validarUsuarioEnAreaDestino(
                userId: $recibidoPor,
                areaDestinoId: $derivacion->area_destino_id,
            );


            /*
            |--------------------------------------------------------------------------
            | 7. Validar ubicación actual del documento
            |--------------------------------------------------------------------------
            */

            $this->validarDocumentoEnAreaDestino(
                documento: $documento,
                areaDestinoId: $derivacion->area_destino_id,
            );


            /*
            |--------------------------------------------------------------------------
            | 8. Guardar estado anterior
            |--------------------------------------------------------------------------
            */

            $estadoAnteriorId = $derivacion->estado_id;


            /*
            |--------------------------------------------------------------------------
            | 9. Recibir derivación
            |--------------------------------------------------------------------------
            */

            $derivacion->update([
                'estado_id' =>
                    $estadoRecibida->id,

                'fecha_recepcion' =>
                    now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | 10. Registrar movimiento
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarRecepcionDerivacion(
                    documento: $documento,
                    derivacion: $derivacion,
                    userId: $recibidoPor,
                    areaId: $derivacion->area_destino_id,
                    estadoAnteriorId: $estadoAnteriorId,
                    estadoNuevoId: $estadoRecibida->id,
                );


            /*
            |--------------------------------------------------------------------------
            | 11. Registrar auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarRecepcionDerivacion(
                    documento: $documento,
                    derivacion: $derivacion,
                    estadoAnteriorId: $estadoAnteriorId,
                    userId: $recibidoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $derivacion->fresh();

        }, 3);
    }


    /*
    |--------------------------------------------------------------------------
    | Obtener estado
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | Validar estado actual
    |--------------------------------------------------------------------------
    */

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
                        'La derivación no puede ser recibida porque actualmente se encuentra en estado %s.',
                        $estadoActual?->codigo ?? 'DESCONOCIDO'
                    ),
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validar receptor
    |--------------------------------------------------------------------------
    */

    private function validarUsuarioEnAreaDestino(
        int $userId,
        int $areaDestinoId
    ): void {
        $asignacionVigente = UserCargo::query()
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

        if (! $asignacionVigente) {
            throw ValidationException::withMessages([
                'recibido_por' =>
                    'El usuario no pertenece actualmente al área destino de la derivación.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validar ubicación del documento
    |--------------------------------------------------------------------------
    */

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
                    'El documento ya no se encuentra en el área destino de esta derivación.',
            ]);
        }
    }
}