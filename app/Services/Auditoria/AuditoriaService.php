<?php

namespace App\Services\Auditoria;

use App\Models\Auditoria;

use App\Models\Documento;
use App\Models\DocumentoExterno;
use App\Models\RemitenteExterno;
use App\Models\DocumentoInterno;

use App\Models\Derivacion;
use App\Models\Observacion;
use App\Models\Subsanacion;

class AuditoriaService
{
    /**
     * Registra un evento de auditoría.
     */
    public function registrar(
        string $evento,
        ?string $tabla = null,
        ?int $registroId = null,
        ?array $valoresAnteriores = null,
        ?array $valoresNuevos = null,
        ?int $userId = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {

        return Auditoria::create([
            'user_id' => $userId,

            'evento' => $evento,

            'tabla' => $tabla,

            'registro_id' => $registroId,

            'valores_anteriores' => $valoresAnteriores,

            'valores_nuevos' => $valoresNuevos,

            'ip' => $ip,

            'user_agent' => $userAgent,

            'fecha' => now(),
        ]);
    }

    /**
     * Registra la auditoría correspondiente al nacimiento
     * de un documento externo.
     */
    public function registrarDocumentoExterno(
        Documento $documento,
        DocumentoExterno $documentoExterno,
        RemitenteExterno $remitente,
        ?int $userId = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {

        return $this->registrar(
            evento: 'DOCUMENTO_EXTERNO_REGISTRADO',

            tabla: 'documentos',

            registroId: $documento->id,

            valoresAnteriores: null,

            valoresNuevos: [
                'documento' => [
                    'id' => $documento->id,
                    'codigo' => $documento->codigo,
                    'origen' => $documento->origen,
                    'tipo_documento_id' => $documento->tipo_documento_id,
                    'estado_id' => $documento->estado_id,
                    'prioridad_id' => $documento->prioridad_id,
                    'area_actual_id' => $documento->area_actual_id,
                    'numero_documento' => $documento->numero_documento,
                    'anio' => $documento->anio,
                    'sigla' => $documento->sigla,
                    'asunto' => $documento->asunto,
                    'folios' => $documento->folios,
                    'fecha_documento' => $documento->fecha_documento,
                    'fecha_registro' => $documento->fecha_registro,
                    'confidencial' => $documento->confidencial,
                    'requiere_respuesta' => $documento->requiere_respuesta,
                ],

                'documento_externo' => [
                    'id' => $documentoExterno->id,
                    'remitente_externo_id' =>
                    $documentoExterno->remitente_externo_id,

                    'canal_ingreso' =>
                    $documentoExterno->canal_ingreso,

                    'codigo_seguimiento' =>
                    $documentoExterno->codigo_seguimiento,
                ],

                'remitente' => [
                    'id' => $remitente->id,
                    'tipo_persona' => $remitente->tipo_persona,
                    'tipo_documento_identidad' =>
                    $remitente->tipo_documento_identidad,

                    'numero_documento_identidad' =>
                    $remitente->numero_documento_identidad,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    /**
     * Registra la auditoría del nacimiento
     * de un documento interno.
     */
    public function registrarDocumentoInterno(
        Documento $documento,
        DocumentoInterno $documentoInterno,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {

        return $this->registrar(
            evento: 'DOCUMENTO_INTERNO_REGISTRADO',

            tabla: 'documentos',

            registroId: $documento->id,

            valoresAnteriores: null,

            valoresNuevos: [
                'documento' => [
                    'id' =>
                    $documento->id,

                    'codigo' =>
                    $documento->codigo,

                    'origen' =>
                    $documento->origen,

                    'tipo_documento_id' =>
                    $documento->tipo_documento_id,

                    'estado_id' =>
                    $documento->estado_id,

                    'prioridad_id' =>
                    $documento->prioridad_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,

                    'registrado_por' =>
                    $documento->registrado_por,

                    'numero_documento' =>
                    $documento->numero_documento,

                    'anio' =>
                    $documento->anio,

                    'sigla' =>
                    $documento->sigla,

                    'asunto' =>
                    $documento->asunto,

                    'folios' =>
                    $documento->folios,

                    'fecha_documento' =>
                    $documento->fecha_documento,

                    'fecha_registro' =>
                    $documento->fecha_registro,

                    'confidencial' =>
                    $documento->confidencial,

                    'requiere_respuesta' =>
                    $documento->requiere_respuesta,
                ],

                'documento_interno' => [
                    'id' =>
                    $documentoInterno->id,

                    'area_origen_id' =>
                    $documentoInterno->area_origen_id,

                    'emisor_user_id' =>
                    $documentoInterno->emisor_user_id,

                    'requiere_firma' =>
                    $documentoInterno->requiere_firma,

                    'fecha_emision' =>
                    $documentoInterno->fecha_emision,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    /**
     * Registra la correspondiente a la derivación de un documento.
     */
    public function registrarDerivacion(
        Documento $documento,
        Derivacion $derivacion,
        ?int $estadoAnteriorDocumentoId,
        ?int $areaAnteriorId,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'DOCUMENTO_DERIVADO',

            tabla: 'derivaciones',

            registroId: $derivacion->id,

            valoresAnteriores: [
                'documento' => [
                    'estado_id' =>
                    $estadoAnteriorDocumentoId,

                    'area_actual_id' =>
                    $areaAnteriorId,
                ],
            ],

            valoresNuevos: [
                'documento' => [
                    'id' =>
                    $documento->id,

                    'codigo' =>
                    $documento->codigo,

                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],

                'derivacion' => [
                    'id' =>
                    $derivacion->id,

                    'documento_id' =>
                    $derivacion->documento_id,

                    'area_origen_id' =>
                    $derivacion->area_origen_id,

                    'area_destino_id' =>
                    $derivacion->area_destino_id,

                    'derivado_por' =>
                    $derivacion->derivado_por,

                    'responsable_destino_id' =>
                    $derivacion->responsable_destino_id,

                    'estado_id' =>
                    $derivacion->estado_id,

                    'indicacion' =>
                    $derivacion->indicacion,

                    'fecha_derivacion' =>
                    $derivacion->fecha_derivacion,

                    'fecha_limite' =>
                    $derivacion->fecha_limite,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    public function registrarRecepcionDerivacion(
        Documento $documento,
        Derivacion $derivacion,
        int $estadoAnteriorId,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'DERIVACION_RECIBIDA',

            tabla: 'derivaciones',

            registroId: $derivacion->id,

            valoresAnteriores: [
                'derivacion' => [
                    'estado_id' =>
                    $estadoAnteriorId,

                    'fecha_recepcion' =>
                    null,
                ],
            ],

            valoresNuevos: [
                'derivacion' => [
                    'id' =>
                    $derivacion->id,

                    'documento_id' =>
                    $derivacion->documento_id,

                    'area_origen_id' =>
                    $derivacion->area_origen_id,

                    'area_destino_id' =>
                    $derivacion->area_destino_id,

                    'responsable_destino_id' =>
                    $derivacion->responsable_destino_id,

                    'estado_id' =>
                    $derivacion->estado_id,

                    'fecha_derivacion' =>
                    $derivacion->fecha_derivacion,

                    'fecha_recepcion' =>
                    $derivacion->fecha_recepcion,
                ],

                'documento' => [
                    'id' =>
                    $documento->id,

                    'codigo' =>
                    $documento->codigo,

                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    public function registrarAtencionDerivacion(
        Documento $documento,
        Derivacion $derivacion,
        string $resultado,
        int $estadoAnteriorDerivacionId,
        int $estadoAnteriorDocumentoId,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'DERIVACION_ATENDIDA',

            tabla: 'derivaciones',

            registroId: $derivacion->id,

            valoresAnteriores: [
                'derivacion' => [
                    'estado_id' =>
                    $estadoAnteriorDerivacionId,

                    'fecha_atencion' =>
                    null,
                ],

                'documento' => [
                    'estado_id' =>
                    $estadoAnteriorDocumentoId,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            valoresNuevos: [
                'resultado' =>
                $resultado,

                'derivacion' => [
                    'id' =>
                    $derivacion->id,

                    'documento_id' =>
                    $derivacion->documento_id,

                    'area_origen_id' =>
                    $derivacion->area_origen_id,

                    'area_destino_id' =>
                    $derivacion->area_destino_id,

                    'estado_id' =>
                    $derivacion->estado_id,

                    'fecha_recepcion' =>
                    $derivacion->fecha_recepcion,

                    'fecha_atencion' =>
                    $derivacion->fecha_atencion,
                ],

                'documento' => [
                    'id' =>
                    $documento->id,

                    'codigo' =>
                    $documento->codigo,

                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    public function registrarArchivadoDocumento(
        Documento $documento,
        int $estadoAnteriorId,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'DOCUMENTO_ARCHIVADO',

            tabla: 'documentos',

            registroId: $documento->id,

            valoresAnteriores: [
                'documento' => [
                    'estado_id' =>
                    $estadoAnteriorId,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            valoresNuevos: [
                'documento' => [
                    'id' =>
                    $documento->id,

                    'codigo' =>
                    $documento->codigo,

                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    public function registrarCancelacionDerivacion(
        Documento $documento,
        Derivacion $derivacion,
        int $estadoAnteriorDerivacionId,
        ?int $areaAnteriorDocumentoId,
        string $motivo,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'DERIVACION_CANCELADA',

            tabla: 'derivaciones',

            registroId: $derivacion->id,

            valoresAnteriores: [
                'derivacion' => [
                    'estado_id' =>
                    $estadoAnteriorDerivacionId,

                    'fecha_recepcion' =>
                    $derivacion->fecha_recepcion,

                    'fecha_atencion' =>
                    $derivacion->fecha_atencion,
                ],

                'documento' => [
                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $areaAnteriorDocumentoId,
                ],
            ],

            valoresNuevos: [
                'motivo' =>
                trim($motivo),

                'derivacion' => [
                    'id' =>
                    $derivacion->id,

                    'documento_id' =>
                    $derivacion->documento_id,

                    'area_origen_id' =>
                    $derivacion->area_origen_id,

                    'area_destino_id' =>
                    $derivacion->area_destino_id,

                    'estado_id' =>
                    $derivacion->estado_id,

                    'fecha_recepcion' =>
                    $derivacion->fecha_recepcion,

                    'fecha_atencion' =>
                    $derivacion->fecha_atencion,
                ],

                'documento' => [
                    'id' =>
                    $documento->id,

                    'codigo' =>
                    $documento->codigo,

                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    public function registrarAdmisionDocumentoExterno(
        Documento $documento,
        int $estadoAnteriorId,
        ?int $areaAnteriorId,
        ?string $observacion,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'DOCUMENTO_EXTERNO_ADMITIDO',

            tabla: 'documentos',

            registroId: $documento->id,

            valoresAnteriores: [
                'documento' => [
                    'estado_id' =>
                    $estadoAnteriorId,

                    'area_actual_id' =>
                    $areaAnteriorId,
                ],
            ],

            valoresNuevos: [
                'observacion' =>
                $observacion !== null
                    ? trim($observacion)
                    : null,

                'documento' => [
                    'id' =>
                    $documento->id,

                    'codigo' =>
                    $documento->codigo,

                    'origen' =>
                    $documento->origen instanceof \BackedEnum
                        ? $documento->origen->value
                        : $documento->origen,

                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }
    public function registrarRechazoDerivacion(
        Documento $documento,
        Derivacion $derivacion,
        int $estadoAnteriorDerivacionId,
        ?int $areaAnteriorDocumentoId,
        string $motivo,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'DERIVACION_RECHAZADA',

            tabla: 'derivaciones',

            registroId: $derivacion->id,

            valoresAnteriores: [
                'derivacion' => [
                    'estado_id' =>
                    $estadoAnteriorDerivacionId,

                    'fecha_recepcion' =>
                    $derivacion->fecha_recepcion,

                    'fecha_atencion' =>
                    $derivacion->fecha_atencion,
                ],

                'documento' => [
                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $areaAnteriorDocumentoId,
                ],
            ],

            valoresNuevos: [
                'motivo' =>
                trim($motivo),

                'derivacion' => [
                    'id' =>
                    $derivacion->id,

                    'documento_id' =>
                    $derivacion->documento_id,

                    'area_origen_id' =>
                    $derivacion->area_origen_id,

                    'area_destino_id' =>
                    $derivacion->area_destino_id,

                    'estado_id' =>
                    $derivacion->estado_id,

                    'fecha_recepcion' =>
                    $derivacion->fecha_recepcion,

                    'fecha_atencion' =>
                    $derivacion->fecha_atencion,
                ],

                'documento' => [
                    'id' =>
                    $documento->id,

                    'codigo' =>
                    $documento->codigo,

                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    public function registrarObservacionDocumento(
        Documento $documento,
        Observacion $observacion,
        int $estadoAnteriorId,
        int $estadoNuevoId,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'DOCUMENTO_OBSERVADO',

            tabla: 'observaciones',

            registroId: $observacion->id,

            valoresAnteriores: [
                'documento' => [
                    'estado_id' =>
                    $estadoAnteriorId,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            valoresNuevos: [
                'observacion' => [
                    'id' =>
                    $observacion->id,

                    'documento_id' =>
                    $observacion->documento_id,

                    'area_id' =>
                    $observacion->area_id,

                    'registrado_por' =>
                    $observacion->registrado_por,

                    'tipo' =>
                    $observacion->tipo,

                    'detalle' =>
                    $observacion->detalle,

                    'requiere_subsanacion' =>
                    $observacion->requiere_subsanacion,

                    'fecha_observacion' =>
                    $observacion->fecha_observacion,

                    'fecha_subsanacion_limite' =>
                    $observacion->fecha_subsanacion_limite,
                ],

                'documento' => [
                    'estado_id' =>
                    $estadoNuevoId,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    public function registrarSubsanacion(
        Documento $documento,
        Observacion $observacion,
        Subsanacion $subsanacion,
        ?int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'SUBSANACION_REGISTRADA',

            tabla: 'subsanaciones',

            registroId: $subsanacion->id,

            valoresAnteriores: [
                'documento' => [
                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],

                'observacion' => [
                    'id' =>
                    $observacion->id,

                    'requiere_subsanacion' =>
                    $observacion->requiere_subsanacion,

                    'fecha_subsanacion_limite' =>
                    $observacion->fecha_subsanacion_limite,
                ],
            ],

            valoresNuevos: [
                'subsanacion' => [
                    'id' =>
                    $subsanacion->id,

                    'observacion_id' =>
                    $subsanacion->observacion_id,

                    'documento_id' =>
                    $subsanacion->documento_id,

                    'registrado_por' =>
                    $subsanacion->registrado_por,

                    'detalle' =>
                    $subsanacion->detalle,

                    'fecha_subsanacion' =>
                    $subsanacion->fecha_subsanacion,

                    'estado_revision' =>
                    $subsanacion->estado_revision
                        instanceof \BackedEnum
                        ? $subsanacion->estado_revision->value
                        : $subsanacion->estado_revision,

                    'revisado_por' =>
                    null,

                    'fecha_revision' =>
                    null,
                ],

                'documento' => [
                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    //inicio de revisión de subsanación
    public function registrarInicioRevisionSubsanacion(
        Documento $documento,
        Subsanacion $subsanacion,
        string $estadoAnterior,
        string $estadoNuevo,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'SUBSANACION_REVISION_INICIADA',

            tabla: 'subsanaciones',

            registroId: $subsanacion->id,

            valoresAnteriores: [
                'subsanacion' => [
                    'estado_revision' =>
                    $estadoAnterior,

                    'revisado_por' =>
                    null,
                ],
            ],

            valoresNuevos: [
                'subsanacion' => [
                    'estado_revision' =>
                    $estadoNuevo,

                    'revisado_por' =>
                    $subsanacion->revisado_por,
                ],

                'documento' => [
                    'id' =>
                    $documento->id,

                    'estado_id' =>
                    $documento->estado_id,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }

    //resolución
    public function registrarResolucionSubsanacion(
        Documento $documento,
        Observacion $observacion,
        Subsanacion $subsanacion,
        string $estadoAnteriorSubsanacion,
        string $resultado,
        int $estadoAnteriorDocumentoId,
        int $estadoNuevoDocumentoId,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Auditoria {
        return $this->registrar(
            evento: 'SUBSANACION_RESUELTA',

            tabla: 'subsanaciones',

            registroId: $subsanacion->id,

            valoresAnteriores: [
                'subsanacion' => [
                    'estado_revision' =>
                    $estadoAnteriorSubsanacion,
                ],

                'documento' => [
                    'estado_id' =>
                    $estadoAnteriorDocumentoId,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            valoresNuevos: [
                'resultado' =>
                $resultado,

                'subsanacion' => [
                    'estado_revision' =>
                    $subsanacion->estado_revision
                        instanceof \BackedEnum
                        ? $subsanacion->estado_revision->value
                        : $subsanacion->estado_revision,

                    'revisado_por' =>
                    $subsanacion->revisado_por,

                    'fecha_revision' =>
                    $subsanacion->fecha_revision,

                    'observacion_revision' =>
                    $subsanacion->observacion_revision,
                ],

                'observacion' => [
                    'id' =>
                    $observacion->id,

                    'requiere_subsanacion' =>
                    $observacion->requiere_subsanacion,
                ],

                'documento' => [
                    'estado_id' =>
                    $estadoNuevoDocumentoId,

                    'area_actual_id' =>
                    $documento->area_actual_id,
                ],
            ],

            userId: $userId,

            ip: $ip,

            userAgent: $userAgent,
        );
    }
}
