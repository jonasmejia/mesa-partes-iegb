<?php

namespace App\Services\Movimientos;

use App\Models\Documento;
use App\Models\DocumentoExterno;
use App\Models\Movimiento;
use App\Models\DocumentoInterno;

use App\Models\Derivacion;
use App\Models\Observacion;
use App\Models\Subsanacion;


class MovimientoService
{
    /**
     * Registra el movimiento inicial de un documento externo.
     */
    public function registrarDocumentoExterno(
        Documento $documento,
        DocumentoExterno $documentoExterno,
        ?int $userId = null,
        ?int $areaId = null
    ): Movimiento {
        return Movimiento::create([
            'documento_id' => $documento->id,

            'user_id' => $userId,

            'area_id' => $areaId,

            'tipo_movimiento' => 'REGISTRO',

            'estado_anterior_id' => null,

            'estado_nuevo_id' => $documento->estado_id,

            'entidad_tipo' => 'DOCUMENTO_EXTERNO',

            'entidad_id' => $documentoExterno->id,

            'detalle' => sprintf(
                'Documento externo %s registrado mediante Mesa de Partes.',
                $documento->codigo
            ),

            'fecha_movimiento' => now(),
        ]);
    }

    /**
     * Registra el movimiento inicial de un documento interno.
     */
    public function registrarDocumentoInterno(
        Documento $documento,
        DocumentoInterno $documentoInterno,
        int $userId,
        int $areaId
    ): Movimiento {

        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'REGISTRO',

            'estado_anterior_id' =>
            null,

            'estado_nuevo_id' =>
            $documento->estado_id,

            'entidad_tipo' =>
            'DOCUMENTO_INTERNO',

            'entidad_id' =>
            $documentoInterno->id,

            'detalle' =>
            sprintf(
                'Documento interno %s registrado por el área de origen.',
                $documento->codigo
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }

    /**
     * Registra la derivación de un documento.
     */
    public function registrarDerivacion(
        Documento $documento,
        Derivacion $derivacion,
        int $userId,
        int $areaOrigenId,
        int $areaDestinoId,
        ?int $estadoAnteriorId,
        int $estadoNuevoId,
    ): Movimiento {
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            /*
        |--------------------------------------------------------------------------
        | El movimiento ocurre desde el área que deriva
        |--------------------------------------------------------------------------
        */

            'area_id' =>
            $areaOrigenId,

            'tipo_movimiento' =>
            'DERIVACION',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'DERIVACION',

            'entidad_id' =>
            $derivacion->id,

            'detalle' =>
            sprintf(
                'Documento %s derivado del área %d al área %d.',
                $documento->codigo,
                $areaOrigenId,
                $areaDestinoId
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }


    public function registrarRecepcionDerivacion(
        Documento $documento,
        Derivacion $derivacion,
        int $userId,
        int $areaId,
        int $estadoAnteriorId,
        int $estadoNuevoId,
    ): Movimiento {
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'RECEPCION',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'DERIVACION',

            'entidad_id' =>
            $derivacion->id,

            'detalle' =>
            sprintf(
                'Derivación del documento %s recibida por el área destino.',
                $documento->codigo
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }

    public function registrarAtencionDerivacion(
        Documento $documento,
        Derivacion $derivacion,
        int $userId,
        int $areaId,
        int $estadoAnteriorId,
        int $estadoNuevoId,
        string $resultado,
    ): Movimiento {
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'ATENCION',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'DERIVACION',

            'entidad_id' =>
            $derivacion->id,

            'detalle' =>
            sprintf(
                'Derivación del documento %s atendida. Resultado: %s.',
                $documento->codigo,
                $resultado
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }

    public function registrarDocumentoAtendido(
        Documento $documento,
        int $userId,
        int $areaId,
        int $estadoAnteriorId,
        int $estadoNuevoId,
        int $derivacionId,
    ): Movimiento {
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'CAMBIO_ESTADO',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'DOCUMENTO',

            'entidad_id' =>
            $documento->id,

            'detalle' =>
            sprintf(
                'Documento %s marcado como atendido al concluir la derivación %d.',
                $documento->codigo,
                $derivacionId
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }

    public function registrarArchivadoDocumento(
        Documento $documento,
        int $userId,
        int $areaId,
        int $estadoAnteriorId,
        int $estadoNuevoId,
    ): Movimiento {
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'ARCHIVADO',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'DOCUMENTO',

            'entidad_id' =>
            $documento->id,

            'detalle' =>
            sprintf(
                'Documento %s archivado.',
                $documento->codigo
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }

    public function registrarCancelacionDerivacion(
        Documento $documento,
        Derivacion $derivacion,
        int $userId,
        int $areaId,
        int $estadoAnteriorId,
        int $estadoNuevoId,
        string $motivo,
    ): Movimiento {
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'CANCELACION',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'DERIVACION',

            'entidad_id' =>
            $derivacion->id,

            'detalle' =>
            sprintf(
                'Derivación del documento %s cancelada. Motivo: %s',
                $documento->codigo,
                trim($motivo)
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }

    public function registrarAdmisionDocumentoExterno(
        Documento $documento,
        int $userId,
        int $areaId,
        int $estadoAnteriorId,
        int $estadoNuevoId,
        ?string $observacion = null,
    ): Movimiento {
        $detalle = sprintf(
            'Documento externo %s admitido y asignado inicialmente al área %d.',
            $documento->codigo,
            $areaId
        );

        if ($observacion !== null && trim($observacion) !== '') {
            $detalle .= ' Observación: ' . trim($observacion);
        }

        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'ADMISION',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'DOCUMENTO',

            'entidad_id' =>
            $documento->id,

            'detalle' =>
            $detalle,

            'fecha_movimiento' =>
            now(),
        ]);
    }

    public function registrarRechazoDerivacion(
        Documento $documento,
        Derivacion $derivacion,
        int $userId,
        int $areaId,
        int $estadoAnteriorId,
        int $estadoNuevoId,
        string $motivo,
    ): Movimiento {
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'RECHAZO',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'DERIVACION',

            'entidad_id' =>
            $derivacion->id,

            'detalle' =>
            sprintf(
                'Derivación del documento %s rechazada por el área destino. Motivo: %s',
                $documento->codigo,
                trim($motivo)
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }
    public function registrarObservacionDocumento(
        Documento $documento,
        Observacion $observacion,
        int $userId,
        int $areaId,
        int $estadoAnteriorId,
        int $estadoNuevoId,
    ): Movimiento {
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'OBSERVACION',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'OBSERVACION',

            'entidad_id' =>
            $observacion->id,

            'detalle' =>
            sprintf(
                'Se registró una observación al documento %s. Tipo: %s. %s',
                $documento->codigo,
                $observacion->tipo,
                $observacion->requiere_subsanacion
                    ? 'Requiere subsanación.'
                    : 'No requiere subsanación.'
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }
    public function registrarSubsanacion(
        Documento $documento,
        Subsanacion $subsanacion,
        ?int $userId,
        ?int $areaId,
        int $estadoDocumentoId,
    ): Movimiento {
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'SUBSANACION',

            'estado_anterior_id' =>
            $estadoDocumentoId,

            'estado_nuevo_id' =>
            $estadoDocumentoId,

            'entidad_tipo' =>
            'SUBSANACION',

            'entidad_id' =>
            $subsanacion->id,

            'detalle' =>
            sprintf(
                'Se registró una subsanación para el documento %s.',
                $documento->codigo
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }

   

    //Inicio de revisión de subsanación
    public function registrarInicioRevisionSubsanacion(
        Documento $documento,
        Subsanacion $subsanacion,
        int $userId,
        int $areaId,
        int $estadoAnteriorId,
        int $estadoNuevoId,
    ): Movimiento {
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'REVISION_SUBSANACION',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'SUBSANACION',

            'entidad_id' =>
            $subsanacion->id,

            'detalle' =>
            sprintf(
                'Se inició la revisión de la subsanación %d del documento %s.',
                $subsanacion->id,
                $documento->codigo
            ),

            'fecha_movimiento' =>
            now(),
        ]);
    }

    //Resolución de subsanación

    public function registrarResolucionSubsanacion(
        Documento $documento,
        Subsanacion $subsanacion,
        int $userId,
        int $areaId,
        int $estadoAnteriorId,
        int $estadoNuevoId,
        string $resultado,
        ?string $observacionRevision = null,
    ): Movimiento {
        $detalle = sprintf(
            'La subsanación %d del documento %s fue %s.',
            $subsanacion->id,
            $documento->codigo,
            $resultado
        );

        if (
            $observacionRevision !== null
            &&
            trim($observacionRevision) !== ''
        ) {
            $detalle .=
                ' Observación: '
                . trim($observacionRevision);
        }

        //Resolución
        return Movimiento::create([
            'documento_id' =>
            $documento->id,

            'user_id' =>
            $userId,

            'area_id' =>
            $areaId,

            'tipo_movimiento' =>
            'RESOLUCION_SUBSANACION',

            'estado_anterior_id' =>
            $estadoAnteriorId,

            'estado_nuevo_id' =>
            $estadoNuevoId,

            'entidad_tipo' =>
            'SUBSANACION',

            'entidad_id' =>
            $subsanacion->id,

            'detalle' =>
            $detalle,

            'fecha_movimiento' =>
            now(),
        ]);
    }

    //Levantamiento de observación

    public function registrarLevantamientoObservacion(
    Documento $documento,
    Subsanacion $subsanacion,
    int $userId,
    int $areaId,
    int $estadoAnteriorId,
    int $estadoNuevoId,
): Movimiento {
    return Movimiento::create([
        'documento_id' =>
            $documento->id,

        'user_id' =>
            $userId,

        'area_id' =>
            $areaId,

        'tipo_movimiento' =>
            'LEVANTAMIENTO_OBSERVACION',

        'estado_anterior_id' =>
            $estadoAnteriorId,

        'estado_nuevo_id' =>
            $estadoNuevoId,

        'entidad_tipo' =>
            'DOCUMENTO',

        'entidad_id' =>
            $documento->id,

        'detalle' =>
            sprintf(
                'Se levantó la observación del documento %s al aceptarse la subsanación %d.',
                $documento->codigo,
                $subsanacion->id
            ),

        'fecha_movimiento' =>
            now(),
    ]);
}
}
