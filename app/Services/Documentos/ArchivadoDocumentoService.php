<?php

namespace App\Services\Documentos;

use App\Models\Documento;
use App\Models\Estado;
use App\Models\UserCargo;
use App\Services\Auditoria\AuditoriaService;
use App\Services\Movimientos\MovimientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchivadoDocumentoService
{
    public function __construct(
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }

    public function archivar(
        Documento $documento,
        int $archivadoPor,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Documento {
        return DB::transaction(function () use (
            $documento,
            $archivadoPor,
            $ip,
            $userAgent
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
            | 2. Obtener estados
            |--------------------------------------------------------------------------
            */

            $estadoAtendido =
                $this->obtenerEstadoDocumento(
                    'DOC_ATENDIDO'
                );

            $estadoArchivado =
                $this->obtenerEstadoDocumento(
                    'DOC_ARCHIVADO'
                );


            /*
            |--------------------------------------------------------------------------
            | 3. Validar estado actual
            |--------------------------------------------------------------------------
            */

            $this->validarEstadoActual(
                documento: $documento,
                estadoAtendidoId: $estadoAtendido->id,
            );


            /*
            |--------------------------------------------------------------------------
            | 4. Validar área actual
            |--------------------------------------------------------------------------
            */

            if ($documento->area_actual_id === null) {
                throw ValidationException::withMessages([
                    'documento' =>
                        'El documento no tiene un área responsable asignada.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 5. Validar usuario que archiva
            |--------------------------------------------------------------------------
            */

            $this->validarUsuarioEnArea(
                userId: $archivadoPor,
                areaId: $documento->area_actual_id,
            );


            /*
            |--------------------------------------------------------------------------
            | 6. Guardar estado anterior
            |--------------------------------------------------------------------------
            */

            $estadoAnteriorId =
                $documento->estado_id;


            /*
            |--------------------------------------------------------------------------
            | 7. Archivar documento
            |--------------------------------------------------------------------------
            */

            $documento->update([
                'estado_id' =>
                    $estadoArchivado->id,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 8. Registrar movimiento
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarArchivadoDocumento(
                    documento: $documento,
                    userId: $archivadoPor,
                    areaId: $documento->area_actual_id,
                    estadoAnteriorId: $estadoAnteriorId,
                    estadoNuevoId: $estadoArchivado->id,
                );


            /*
            |--------------------------------------------------------------------------
            | 9. Registrar auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarArchivadoDocumento(
                    documento: $documento,
                    estadoAnteriorId: $estadoAnteriorId,
                    userId: $archivadoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $documento->fresh();

        }, 3);
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
        Documento $documento,
        int $estadoAtendidoId
    ): void {
        if (
            (int) $documento->estado_id
            !==
            (int) $estadoAtendidoId
        ) {
            $estadoActual = Estado::find(
                $documento->estado_id
            );

            throw ValidationException::withMessages([
                'documento' =>
                    sprintf(
                        'El documento no puede archivarse porque actualmente se encuentra en estado %s.',
                        $estadoActual?->codigo ?? 'DESCONOCIDO'
                    ),
            ]);
        }
    }


    private function validarUsuarioEnArea(
        int $userId,
        int $areaId
    ): void {
        $asignacion = UserCargo::query()
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

        if (! $asignacion) {
            throw ValidationException::withMessages([
                'archivado_por' =>
                    'El usuario no pertenece actualmente al área responsable del documento.',
            ]);
        }
    }
}