<?php

namespace App\Services\Subsanaciones;

use App\Enums\EstadoRevisionSubsanacion;
use App\Models\Documento;
use App\Models\Estado;
use App\Models\Observacion;
use App\Models\Subsanacion;
use App\Services\Auditoria\AuditoriaService;
use App\Services\Movimientos\MovimientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Enums\EstadoAmbito;


class RegistroSubsanacionService
{
    public function __construct(
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }

    public function registrar(
        Observacion $observacion,
        ?int $registradoPor,
        string $detalle,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Subsanacion {
        return DB::transaction(function () use (
            $observacion,
            $registradoPor,
            $detalle,
            $ip,
            $userAgent
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. Bloquear observación
            |--------------------------------------------------------------------------
            */

            $observacion = Observacion::query()
                ->whereKey($observacion->id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 2. Bloquear documento
            |--------------------------------------------------------------------------
            */

            $documento = Documento::query()
                ->whereKey($observacion->documento_id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 3. Validar observación
            |--------------------------------------------------------------------------
            */

            $this->validarObservacion(
                observacion: $observacion,
                documento: $documento,
            );


            /*
            |--------------------------------------------------------------------------
            | 4. Validar detalle
            |--------------------------------------------------------------------------
            */

            $detalle = trim($detalle);

            $this->validarDetalle(
                detalle: $detalle,
            );


            /*
            |--------------------------------------------------------------------------
            | 5. Validar estado documental
            |--------------------------------------------------------------------------
            */

            $estadoObservado =
                $this->obtenerEstadoDocumento(
                    'DOC_OBSERVADO'
                );

            if (
                (int) $documento->estado_id
                !==
                (int) $estadoObservado->id
            ) {
                $estadoActual = Estado::find(
                    $documento->estado_id
                );

                throw ValidationException::withMessages([
                    'documento' => sprintf(
                        'No puede registrarse una subsanación porque el documento se encuentra en estado %s.',
                        $estadoActual?->codigo ?? 'DESCONOCIDO'
                    ),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 6. Evitar subsanación activa duplicada
            |--------------------------------------------------------------------------
            */

            $this->validarSinSubsanacionActiva(
                observacion: $observacion
            );


            /*
            |--------------------------------------------------------------------------
            | 7. Crear subsanación
            |--------------------------------------------------------------------------
            */

            $subsanacion = Subsanacion::create([
                'observacion_id' =>
                    $observacion->id,

                'documento_id' =>
                    $documento->id,

                'registrado_por' =>
                    $registradoPor,

                'detalle' =>
                    $detalle,

                'fecha_subsanacion' =>
                    now(),

                'estado_revision' =>
                    EstadoRevisionSubsanacion::PENDIENTE,

                'revisado_por' =>
                    null,

                'fecha_revision' =>
                    null,

                'observacion_revision' =>
                    null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 8. Registrar movimiento
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarSubsanacion(
                    documento: $documento,
                    subsanacion: $subsanacion,
                    userId: $registradoPor,
                    areaId: $documento->area_actual_id,
                    estadoDocumentoId: $documento->estado_id,
                );


            /*
            |--------------------------------------------------------------------------
            | 9. Registrar auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarSubsanacion(
                    documento: $documento,
                    observacion: $observacion,
                    subsanacion: $subsanacion,
                    userId: $registradoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $subsanacion->fresh();

        }, 3);
    }


    private function validarObservacion(
        Observacion $observacion,
        Documento $documento,
    ): void {
        if (
            (int) $observacion->documento_id
            !==
            (int) $documento->id
        ) {
            throw ValidationException::withMessages([
                'observacion' =>
                    'La observación no corresponde al documento indicado.',
            ]);
        }

        if (! $observacion->requiere_subsanacion) {
            throw ValidationException::withMessages([
                'observacion' =>
                    'La observación seleccionada no requiere subsanación.',
            ]);
        }
    }


    private function validarDetalle(
        string $detalle
    ): void {
        if ($detalle === '') {
            throw ValidationException::withMessages([
                'detalle' =>
                    'Debe indicar el detalle de la subsanación presentada.',
            ]);
        }

        if (mb_strlen($detalle) < 5) {
            throw ValidationException::withMessages([
                'detalle' =>
                    'El detalle de la subsanación debe contener al menos 5 caracteres.',
            ]);
        }
    }


    private function validarSinSubsanacionActiva(
        Observacion $observacion
    ): void {
        $existe = Subsanacion::query()
            ->where(
                'observacion_id',
                $observacion->id
            )
            ->whereIn(
                'estado_revision',
                [
                    EstadoRevisionSubsanacion::PENDIENTE->value,
                    EstadoRevisionSubsanacion::EN_REVISION->value,
                ]
            )
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'subsanacion' =>
                    'La observación ya tiene una subsanación pendiente o en revisión.',
            ]);
        }
    }


    private function obtenerEstadoDocumento(
        string $codigo
    ): Estado {
        $estado = Estado::query()
            ->where('codigo', $codigo)
            ->where('ambito', EstadoAmbito::DOCUMENTO->value)
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