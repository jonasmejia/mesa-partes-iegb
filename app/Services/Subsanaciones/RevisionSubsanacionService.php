<?php

namespace App\Services\Subsanaciones;

use App\Enums\EstadoRevisionSubsanacion;
use App\Models\Documento;
use App\Models\Estado;
use App\Models\Observacion;
use App\Models\Subsanacion;
use App\Models\UserCargo;
use App\Services\Auditoria\AuditoriaService;
use App\Services\Movimientos\MovimientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use app\Enums\EstadoAmbito;

class RevisionSubsanacionService
{
    public function __construct(
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | INICIAR REVISIÓN
    |--------------------------------------------------------------------------
    */

    public function iniciarRevision(
        Subsanacion $subsanacion,
        int $revisadoPor,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Subsanacion {
        return DB::transaction(function () use (
            $subsanacion,
            $revisadoPor,
            $ip,
            $userAgent
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. Bloquear subsanación
            |--------------------------------------------------------------------------
            */

            $subsanacion = Subsanacion::query()
                ->whereKey($subsanacion->id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 2. Bloquear documento
            |--------------------------------------------------------------------------
            */

            $documento = Documento::query()
                ->whereKey($subsanacion->documento_id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 3. Obtener observación
            |--------------------------------------------------------------------------
            */

            $observacion = Observacion::query()
                ->whereKey($subsanacion->observacion_id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 4. Validar integridad
            |--------------------------------------------------------------------------
            */

            $this->validarIntegridad(
                subsanacion: $subsanacion,
                observacion: $observacion,
                documento: $documento,
            );


            /*
            |--------------------------------------------------------------------------
            | 5. Validar documento observado
            |--------------------------------------------------------------------------
            */

            $estadoDocumentoObservado =
                $this->obtenerEstado(
                    codigo: 'DOC_OBSERVADO',
                    ambito: EstadoAmbito::DOCUMENTO->value,
                );

            if (
                (int) $documento->estado_id
                !==
                (int) $estadoDocumentoObservado->id
            ) {
                throw ValidationException::withMessages([
                    'documento' =>
                    'El documento debe encontrarse en estado DOC_OBSERVADO para revisar una subsanación.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 6. Validar estado actual de subsanación
            |--------------------------------------------------------------------------
            */

            $estadoActual =
                $this->valorEstadoRevision(
                    $subsanacion
                );

            if (
                $estadoActual
                !==
                EstadoRevisionSubsanacion::PENDIENTE->value
            ) {
                throw ValidationException::withMessages([
                    'subsanacion' =>
                    "La subsanación no puede iniciar revisión porque actualmente se encuentra en estado {$estadoActual}.",
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 7. Validar revisor en área responsable
            |--------------------------------------------------------------------------
            */

            if ($documento->area_actual_id === null) {
                throw ValidationException::withMessages([
                    'documento' =>
                    'El documento no tiene un área responsable asignada.',
                ]);
            }

            $this->validarUsuarioEnArea(
                userId: $revisadoPor,
                areaId: $documento->area_actual_id,
            );


            /*
            |--------------------------------------------------------------------------
            | 8. Estados de subsanación para movimiento
            |--------------------------------------------------------------------------
            */

            $estadoPendiente = $this->obtenerEstado(
                codigo: EstadoRevisionSubsanacion::PENDIENTE->value,
                ambito: EstadoAmbito::SUBSANACION->value,
            );

            $estadoEnRevision = $this->obtenerEstado(
                codigo: EstadoRevisionSubsanacion::EN_REVISION->value,
                ambito: EstadoAmbito::SUBSANACION->value,
            );


            /*
            |--------------------------------------------------------------------------
            | 9. Actualizar subsanación
            |--------------------------------------------------------------------------
            */

            $subsanacion->update([
                'estado_revision' =>
                EstadoRevisionSubsanacion::EN_REVISION,

                'revisado_por' =>
                $revisadoPor,

                'fecha_revision' =>
                null,

                'observacion_revision' =>
                null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 10. Movimiento
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarInicioRevisionSubsanacion(
                    documento: $documento,
                    subsanacion: $subsanacion,
                    userId: $revisadoPor,
                    areaId: $documento->area_actual_id,
                    estadoAnteriorId: $estadoPendiente->id,
                    estadoNuevoId: $estadoEnRevision->id,
                );


            /*
            |--------------------------------------------------------------------------
            | 11. Auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarInicioRevisionSubsanacion(
                    documento: $documento,
                    subsanacion: $subsanacion,
                    estadoAnterior: EstadoRevisionSubsanacion::PENDIENTE->value,
                    estadoNuevo: EstadoRevisionSubsanacion::EN_REVISION->value,
                    userId: $revisadoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $subsanacion->fresh();
        }, 3);
    }


    /*
    |--------------------------------------------------------------------------
    | RESOLVER SUBSANACIÓN
    |--------------------------------------------------------------------------
    */

    public function resolver(
        Subsanacion $subsanacion,
        int $revisadoPor,
        EstadoRevisionSubsanacion $resultado,
        ?string $observacionRevision = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Subsanacion {
        return DB::transaction(function () use (
            $subsanacion,
            $revisadoPor,
            $resultado,
            $observacionRevision,
            $ip,
            $userAgent
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. Validar resultado
            |--------------------------------------------------------------------------
            */

            if (! in_array(
                $resultado,
                [
                    EstadoRevisionSubsanacion::ACEPTADA,
                    EstadoRevisionSubsanacion::RECHAZADA,
                ],
                true
            )) {
                throw ValidationException::withMessages([
                    'resultado' =>
                    'El resultado de la revisión debe ser ACEPTADA o RECHAZADA.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 2. Bloquear subsanación
            |--------------------------------------------------------------------------
            */

            $subsanacion = Subsanacion::query()
                ->whereKey($subsanacion->id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 3. Bloquear documento
            |--------------------------------------------------------------------------
            */

            $documento = Documento::query()
                ->whereKey($subsanacion->documento_id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 4. Bloquear observación
            |--------------------------------------------------------------------------
            */

            $observacion = Observacion::query()
                ->whereKey($subsanacion->observacion_id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 5. Validar integridad
            |--------------------------------------------------------------------------
            */

            $this->validarIntegridad(
                subsanacion: $subsanacion,
                observacion: $observacion,
                documento: $documento,
            );


            /*
            |--------------------------------------------------------------------------
            | 6. Debe estar EN_REVISION
            |--------------------------------------------------------------------------
            */

            $estadoActual =
                $this->valorEstadoRevision(
                    $subsanacion
                );

            if (
                $estadoActual
                !==
                EstadoRevisionSubsanacion::EN_REVISION->value
            ) {
                throw ValidationException::withMessages([
                    'subsanacion' =>
                    "La subsanación no puede resolverse porque actualmente se encuentra en estado {$estadoActual}.",
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 7. Validar mismo revisor
            |--------------------------------------------------------------------------
            */

            if (
                $subsanacion->revisado_por !== null
                &&
                (int) $subsanacion->revisado_por
                !==
                (int) $revisadoPor
            ) {
                throw ValidationException::withMessages([
                    'revisado_por' =>
                    'La subsanación está siendo revisada por otro usuario.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 8. Validar usuario en área
            |--------------------------------------------------------------------------
            */

            if ($documento->area_actual_id === null) {
                throw ValidationException::withMessages([
                    'documento' =>
                    'El documento no tiene un área responsable asignada.',
                ]);
            }

            $this->validarUsuarioEnArea(
                userId: $revisadoPor,
                areaId: $documento->area_actual_id,
            );


            /*
            |--------------------------------------------------------------------------
            | 9. Si rechaza, exigir observación
            |--------------------------------------------------------------------------
            */

            $observacionRevision =
                $observacionRevision !== null
                ? trim($observacionRevision)
                : null;

            if (
                $resultado
                ===
                EstadoRevisionSubsanacion::RECHAZADA
                &&
                empty($observacionRevision)
            ) {
                throw ValidationException::withMessages([
                    'observacion_revision' =>
                    'Debe indicar el motivo cuando la subsanación es rechazada.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 10. Obtener estados de subsanación
            |--------------------------------------------------------------------------
            */

            $estadoEnRevision = $this->obtenerEstado(
                codigo: EstadoRevisionSubsanacion::EN_REVISION->value,
                ambito: EstadoAmbito::SUBSANACION->value,
            );

            $estadoResultado = $this->obtenerEstado(
                codigo: $resultado->value,
                ambito: EstadoAmbito::SUBSANACION->value,
            );


            /*
            |--------------------------------------------------------------------------
            | 11. Estado actual del documento
            |--------------------------------------------------------------------------
            */

            $estadoDocumentoAnteriorId =
                $documento->estado_id;

            $estadoDocumentoNuevoId =
                $estadoDocumentoAnteriorId;

            $documentoFueLiberado = false;

            /*
            |--------------------------------------------------------------------------
            | 12. Resolver subsanación
            |--------------------------------------------------------------------------
            */

            $subsanacion->update([
                'estado_revision' =>
                $resultado,

                'revisado_por' =>
                $revisadoPor,

                'fecha_revision' =>
                now(),

                'observacion_revision' =>
                $observacionRevision,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 13. Si es aceptada, levantar observación
            |--------------------------------------------------------------------------
            */



            if (
                $resultado
                ===
                EstadoRevisionSubsanacion::ACEPTADA
            ) {
                $existenObservacionesPendientes =
                    $this->documentoTieneObservacionesPendientes(
                        documentoId: $documento->id
                    );

                if (! $existenObservacionesPendientes) {
                    $estadoEnTramite =
                        $this->obtenerEstado(
                            codigo: 'DOC_EN_TRAMITE',
                            ambito: 'DOCUMENTO',
                        );

                    $documento->update([
                        'estado_id' =>
                        $estadoEnTramite->id,
                    ]);

                    $estadoDocumentoNuevoId =
                        $estadoEnTramite->id;

                    $documentoFueLiberado =
                        true;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 14. Movimiento de la subsanación
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarResolucionSubsanacion(
                    documento: $documento,
                    subsanacion: $subsanacion,
                    userId: $revisadoPor,
                    areaId: $documento->area_actual_id,
                    estadoAnteriorId: $estadoEnRevision->id,
                    estadoNuevoId: $estadoResultado->id,
                    resultado: $resultado->value,
                    observacionRevision: $observacionRevision,
                );


            /*
            |--------------------------------------------------------------------------
            | 15. Si fue aceptada, registrar cambio documental
            |--------------------------------------------------------------------------
            */

            if ($documentoFueLiberado) {
                $this->movimientoService
                    ->registrarLevantamientoObservacion(
                        documento: $documento,
                        subsanacion: $subsanacion,
                        userId: $revisadoPor,
                        areaId: $documento->area_actual_id,
                        estadoAnteriorId: $estadoDocumentoAnteriorId,
                        estadoNuevoId: $estadoDocumentoNuevoId,
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | 16. Auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarResolucionSubsanacion(
                    documento: $documento,
                    observacion: $observacion,
                    subsanacion: $subsanacion,
                    estadoAnteriorSubsanacion: EstadoRevisionSubsanacion::EN_REVISION->value,
                    resultado: $resultado->value,
                    estadoAnteriorDocumentoId: $estadoDocumentoAnteriorId,
                    estadoNuevoDocumentoId: $estadoDocumentoNuevoId,
                    userId: $revisadoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $subsanacion->fresh();
        }, 3);
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDACIONES AUXILIARES
    |--------------------------------------------------------------------------
    */

    private function validarIntegridad(
        Subsanacion $subsanacion,
        Observacion $observacion,
        Documento $documento,
    ): void {
        if (
            (int) $subsanacion->documento_id
            !==
            (int) $documento->id
        ) {
            throw ValidationException::withMessages([
                'subsanacion' =>
                'La subsanación no corresponde al documento indicado.',
            ]);
        }

        if (
            (int) $subsanacion->observacion_id
            !==
            (int) $observacion->id
        ) {
            throw ValidationException::withMessages([
                'subsanacion' =>
                'La subsanación no corresponde a la observación indicada.',
            ]);
        }

        if (
            (int) $observacion->documento_id
            !==
            (int) $documento->id
        ) {
            throw ValidationException::withMessages([
                'observacion' =>
                'La observación no corresponde al documento de la subsanación.',
            ]);
        }

        if (! $observacion->requiere_subsanacion) {
            throw ValidationException::withMessages([
                'observacion' =>
                'La observación no requiere subsanación.',
            ]);
        }
    }


    private function validarUsuarioEnArea(
        int $userId,
        int $areaId
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
                'revisado_por' =>
                'El usuario no pertenece actualmente al área responsable del documento.',
            ]);
        }
    }


    private function obtenerEstado(
        string $codigo,
        string $ambito,
    ): Estado {
        $estado = Estado::query()
            ->where('codigo', $codigo)
            ->where('ambito', $ambito)
            ->first();

        if (! $estado) {
            throw ValidationException::withMessages([
                'estado' =>
                "No se encuentra configurado el estado {$codigo} para el ámbito {$ambito}.",
            ]);
        }

        return $estado;
    }


    private function valorEstadoRevision(
        Subsanacion $subsanacion
    ): string {
        return $subsanacion->estado_revision
            instanceof EstadoRevisionSubsanacion
            ? $subsanacion->estado_revision->value
            : $subsanacion->estado_revision;
    }

    private function existenObservacionesPendientes(
        Documento $documento
    ): bool {
        return Observacion::query()
            ->where(
                'documento_id',
                $documento->id
            )
            ->where(
                'requiere_subsanacion',
                true
            )
            ->whereNotExists(function ($query) {
                $query
                    ->selectRaw('1')
                    ->from('subsanaciones')
                    ->whereColumn(
                        'subsanaciones.observacion_id',
                        'observaciones.id'
                    )
                    ->where(
                        'subsanaciones.estado_revision',
                        EstadoRevisionSubsanacion::ACEPTADA->value
                    );
            })
            ->exists();
    }

    private function documentoTieneObservacionesPendientes(
        int $documentoId
    ): bool {
        return Observacion::query()
            ->where(
                'documento_id',
                $documentoId
            )
            ->where(
                'requiere_subsanacion',
                true
            )
            ->whereDoesntHave(
                'subsanaciones',
                function ($query) {
                    $query->where(
                        'estado_revision',
                        EstadoRevisionSubsanacion::ACEPTADA->value
                    );
                }
            )
            ->exists();
    }
}
