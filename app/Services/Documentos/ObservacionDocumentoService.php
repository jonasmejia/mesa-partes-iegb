<?php

namespace App\Services\Documentos;

use App\Models\Documento;
use App\Models\Estado;
use App\Models\Observacion;
use App\Models\UserCargo;
use App\Services\Auditoria\AuditoriaService;
use App\Services\Movimientos\MovimientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ObservacionDocumentoService
{
    public function __construct(
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }

    public function observar(
        Documento $documento,
        array $datos,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Observacion {
        return DB::transaction(function () use (
            $documento,
            $datos,
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
            | 2. Obtener estado actual permitido
            |--------------------------------------------------------------------------
            */

            $estadoEnTramite = $this->obtenerEstadoDocumento(
                'DOC_EN_TRAMITE'
            );


            /*
            |--------------------------------------------------------------------------
            | 3. Validar estado del documento
            |--------------------------------------------------------------------------
            */

            if (
                (int) $documento->estado_id
                !==
                (int) $estadoEnTramite->id
            ) {
                $estadoActual = Estado::find(
                    $documento->estado_id
                );

                throw ValidationException::withMessages([
                    'documento' => sprintf(
                        'El documento no puede observarse porque actualmente se encuentra en estado %s.',
                        $estadoActual?->codigo ?? 'DESCONOCIDO'
                    ),
                ]);
            }


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
            | 5. Obtener y validar datos
            |--------------------------------------------------------------------------
            */

            $areaId = (int) $documento->area_actual_id;

            $registradoPor =
                $datos['registrado_por'] ?? null;

            $tipo =
                trim((string) ($datos['tipo'] ?? ''));

            $detalle =
                trim((string) ($datos['detalle'] ?? ''));

            $requiereSubsanacion =
                (bool) ($datos['requiere_subsanacion'] ?? false);

            $fechaLimite =
                $datos['fecha_subsanacion_limite'] ?? null;


            $this->validarDatos(
                registradoPor: $registradoPor,
                tipo: $tipo,
                detalle: $detalle,
                requiereSubsanacion: $requiereSubsanacion,
                fechaLimite: $fechaLimite,
            );


            /*
            |--------------------------------------------------------------------------
            | 6. Validar usuario en área actual
            |--------------------------------------------------------------------------
            */

            $this->validarUsuarioEnArea(
                userId: $registradoPor,
                areaId: $areaId,
            );


            /*
            |--------------------------------------------------------------------------
            | 7. Obtener estado DOC_OBSERVADO si corresponde
            |--------------------------------------------------------------------------
            */

            $estadoObservado = null;

            if ($requiereSubsanacion) {
                $estadoObservado =
                    $this->obtenerEstadoDocumento(
                        'DOC_OBSERVADO'
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | 8. Guardar estado anterior
            |--------------------------------------------------------------------------
            */

            $estadoAnteriorId =
                $documento->estado_id;


            /*
            |--------------------------------------------------------------------------
            | 9. Crear observación
            |--------------------------------------------------------------------------
            */

            $observacion = Observacion::create([
                'documento_id' =>
                    $documento->id,

                'area_id' =>
                    $areaId,

                'registrado_por' =>
                    $registradoPor,

                'tipo' =>
                    $tipo,

                'detalle' =>
                    $detalle,

                'requiere_subsanacion' =>
                    $requiereSubsanacion,

                'fecha_observacion' =>
                    now(),

                'fecha_subsanacion_limite' =>
                    $requiereSubsanacion
                        ? $fechaLimite
                        : null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 10. Cambiar estado si requiere subsanación
            |--------------------------------------------------------------------------
            */

            if ($requiereSubsanacion) {
                $documento->update([
                    'estado_id' =>
                        $estadoObservado->id,
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 11. Estado nuevo
            |--------------------------------------------------------------------------
            */

            $estadoNuevoId =
                $requiereSubsanacion
                    ? $estadoObservado->id
                    : $estadoAnteriorId;


            /*
            |--------------------------------------------------------------------------
            | 12. Registrar movimiento
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarObservacionDocumento(
                    documento: $documento,
                    observacion: $observacion,
                    userId: $registradoPor,
                    areaId: $areaId,
                    estadoAnteriorId: $estadoAnteriorId,
                    estadoNuevoId: $estadoNuevoId,
                );


            /*
            |--------------------------------------------------------------------------
            | 13. Registrar auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarObservacionDocumento(
                    documento: $documento,
                    observacion: $observacion,
                    estadoAnteriorId: $estadoAnteriorId,
                    estadoNuevoId: $estadoNuevoId,
                    userId: $registradoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $observacion->fresh();

        }, 3);
    }


    private function validarDatos(
        ?int $registradoPor,
        string $tipo,
        string $detalle,
        bool $requiereSubsanacion,
        mixed $fechaLimite,
    ): void {
        if (! $registradoPor) {
            throw ValidationException::withMessages([
                'registrado_por' =>
                    'Debe indicar el usuario que registra la observación.',
            ]);
        }

        if ($tipo === '') {
            throw ValidationException::withMessages([
                'tipo' =>
                    'Debe indicar el tipo de observación.',
            ]);
        }

        if ($detalle === '') {
            throw ValidationException::withMessages([
                'detalle' =>
                    'Debe indicar el detalle de la observación.',
            ]);
        }

        if (mb_strlen($detalle) < 5) {
            throw ValidationException::withMessages([
                'detalle' =>
                    'El detalle debe contener al menos 5 caracteres.',
            ]);
        }

        if (
            $requiereSubsanacion
            && empty($fechaLimite)
        ) {
            throw ValidationException::withMessages([
                'fecha_subsanacion_limite' =>
                    'Debe indicar la fecha límite cuando la observación requiere subsanación.',
            ]);
        }

        if (
            $requiereSubsanacion
            && $fechaLimite
            && now()->startOfDay()->greaterThanOrEqualTo(
                \Carbon\Carbon::parse($fechaLimite)->startOfDay()
            )
        ) {
            throw ValidationException::withMessages([
                'fecha_subsanacion_limite' =>
                    'La fecha límite de subsanación debe ser posterior a la fecha actual.',
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
                'registrado_por' =>
                    'El usuario no pertenece actualmente al área responsable del documento.',
            ]);
        }
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
}