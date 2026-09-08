<?php

namespace App\Services\Documentos;

use App\Models\Documento;
use App\Models\DocumentoExterno;
use App\Models\Estado;
use App\Models\UserCargo;
use App\Services\Auditoria\AuditoriaService;
use App\Services\Movimientos\MovimientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Enums\OrigenDocumento;

class AdmisionDocumentoExternoService
{
    public function __construct(
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }

    public function admitir(
        Documento $documento,
        int $areaDestinoId,
        int $admitidoPor,
        ?string $observacion = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Documento {
        return DB::transaction(function () use (
            $documento,
            $areaDestinoId,
            $admitidoPor,
            $observacion,
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
            | 2. Validar que sea EXTERNO
            |--------------------------------------------------------------------------
            */

            $this->validarDocumentoExterno($documento);


            /*
            |--------------------------------------------------------------------------
            | 3. Obtener estados
            |--------------------------------------------------------------------------
            */

            $estadoRegistrado =
                $this->obtenerEstadoDocumento(
                    'DOC_REGISTRADO'
                );

            $estadoEnTramite =
                $this->obtenerEstadoDocumento(
                    'DOC_EN_TRAMITE'
                );


            /*
            |--------------------------------------------------------------------------
            | 4. Validar estado actual
            |--------------------------------------------------------------------------
            */

            $this->validarEstadoActual(
                documento: $documento,
                estadoRegistradoId: $estadoRegistrado->id,
            );


            /*
            |--------------------------------------------------------------------------
            | 5. Validar que todavía no tenga área asignada
            |--------------------------------------------------------------------------
            */

            if ($documento->area_actual_id !== null) {
                throw ValidationException::withMessages([
                    'documento' =>
                        'El documento externo ya tiene un área responsable asignada.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 6. Validar área destino
            |--------------------------------------------------------------------------
            */

            $this->validarAreaDestino(
                areaDestinoId: $areaDestinoId
            );


            /*
            |--------------------------------------------------------------------------
            | 7. Validar usuario que admite
            |--------------------------------------------------------------------------
            */

            $this->validarUsuarioConAsignacionActiva(
                userId: $admitidoPor,
            );


            /*
            |--------------------------------------------------------------------------
            | 8. Guardar valores anteriores
            |--------------------------------------------------------------------------
            */

            $estadoAnteriorId =
                $documento->estado_id;

            $areaAnteriorId =
                $documento->area_actual_id;


            /*
            |--------------------------------------------------------------------------
            | 9. Admitir documento
            |--------------------------------------------------------------------------
            */

            $documento->update([
                'estado_id' =>
                    $estadoEnTramite->id,

                'area_actual_id' =>
                    $areaDestinoId,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 10. Registrar movimiento
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarAdmisionDocumentoExterno(
                    documento: $documento,
                    userId: $admitidoPor,
                    areaId: $areaDestinoId,
                    estadoAnteriorId: $estadoAnteriorId,
                    estadoNuevoId: $estadoEnTramite->id,
                    observacion: $observacion,
                );


            /*
            |--------------------------------------------------------------------------
            | 11. Registrar auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarAdmisionDocumentoExterno(
                    documento: $documento,
                    estadoAnteriorId: $estadoAnteriorId,
                    areaAnteriorId: $areaAnteriorId,
                    observacion: $observacion,
                    userId: $admitidoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $documento->fresh();

        }, 3);
    }


    private function validarDocumentoExterno(
        Documento $documento
    ): void {
        if ($documento->origen !== OrigenDocumento::EXTERNO) {
            throw ValidationException::withMessages([
                'documento' =>
                    'La admisión inicial solo aplica a documentos externos.',
            ]);
        }

        $existeDocumentoExterno = DocumentoExterno::query()
            ->where(
                'documento_id',
                $documento->id
            )
            ->exists();

        if (! $existeDocumentoExterno) {
            throw ValidationException::withMessages([
                'documento' =>
                    'El documento no tiene un registro externo asociado.',
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


    private function validarEstadoActual(
        Documento $documento,
        int $estadoRegistradoId
    ): void {
        if (
            (int) $documento->estado_id
            !==
            (int) $estadoRegistradoId
        ) {
            $estadoActual = Estado::find(
                $documento->estado_id
            );

            throw ValidationException::withMessages([
                'documento' =>
                    sprintf(
                        'El documento externo no puede admitirse porque actualmente se encuentra en estado %s.',
                        $estadoActual?->codigo ?? 'DESCONOCIDO'
                    ),
            ]);
        }
    }


    private function validarAreaDestino(
        int $areaDestinoId
    ): void {
        $existeArea = DB::table('areas')
            ->where('id', $areaDestinoId)
            ->where('activo', true)
            ->exists();

        if (! $existeArea) {
            throw ValidationException::withMessages([
                'area_destino_id' =>
                    'El área seleccionada no existe o se encuentra inactiva.',
            ]);
        }
    }


    private function validarUsuarioConAsignacionActiva(
        int $userId
    ): void {
        $asignacion = UserCargo::query()
            ->where('user_id', $userId)
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
                'admitido_por' =>
                    'El usuario que realiza la admisión no tiene una asignación institucional vigente.',
            ]);
        }
    }
}