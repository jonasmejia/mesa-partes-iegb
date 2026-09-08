<?php

namespace App\Enums;

enum EstadoCodigo: string
{
    /*
    |--------------------------------------------------------------------------
    | Documento
    |--------------------------------------------------------------------------
    */

    case DOC_REGISTRADO = 'DOC_REGISTRADO';
    case DOC_EN_TRAMITE = 'DOC_EN_TRAMITE';
    case DOC_OBSERVADO = 'DOC_OBSERVADO';
    case DOC_ATENDIDO = 'DOC_ATENDIDO';
    case DOC_ARCHIVADO = 'DOC_ARCHIVADO';
    case DOC_ANULADO = 'DOC_ANULADO';

        /*
    |--------------------------------------------------------------------------
    | Derivación
    |--------------------------------------------------------------------------
    */

    case DER_PENDIENTE = 'DER_PENDIENTE';
    case DER_RECIBIDA = 'DER_RECIBIDA';
    case DER_EN_ATENCION = 'DER_EN_ATENCION';
    case DER_ATENDIDA = 'DER_ATENDIDA';
    case DER_RECHAZADA = 'DER_RECHAZADA';
    case DER_CANCELADA = 'DER_CANCELADA';

        /*
    |--------------------------------------------------------------------------
    | Expediente
    |--------------------------------------------------------------------------
    */

    case EXP_ABIERTO = 'EXP_ABIERTO';
    case EXP_EN_TRAMITE = 'EXP_EN_TRAMITE';
    case EXP_CERRADO = 'EXP_CERRADO';
    case EXP_ARCHIVADO = 'EXP_ARCHIVADO';


    public function transicionesPermitidas(): array
    {
        return match ($this) {

            /*
        |--------------------------------------------------------------------------
        | Documento
        |--------------------------------------------------------------------------
        */

            self::DOC_REGISTRADO => [
                self::DOC_EN_TRAMITE,
                self::DOC_ANULADO,
            ],

            self::DOC_EN_TRAMITE => [
                self::DOC_OBSERVADO,
                self::DOC_ATENDIDO,
                self::DOC_ANULADO,
            ],

            self::DOC_OBSERVADO => [
                self::DOC_EN_TRAMITE,
                self::DOC_ANULADO,
            ],

            self::DOC_ATENDIDO => [
                self::DOC_ARCHIVADO,
            ],

            self::DOC_ARCHIVADO,
            self::DOC_ANULADO => [],


            /*
        |--------------------------------------------------------------------------
        | Derivación
        |--------------------------------------------------------------------------
        */

            self::DER_PENDIENTE => [
                self::DER_RECIBIDA,
                self::DER_RECHAZADA,
                self::DER_CANCELADA,
            ],

            self::DER_RECIBIDA => [
                self::DER_EN_ATENCION,
                self::DER_CANCELADA,
            ],

            self::DER_EN_ATENCION => [
                self::DER_ATENDIDA,
                self::DER_CANCELADA,
            ],

            self::DER_ATENDIDA,
            self::DER_RECHAZADA,
            self::DER_CANCELADA => [],


            /*
        |--------------------------------------------------------------------------
        | Expediente
        |--------------------------------------------------------------------------
        */

            self::EXP_ABIERTO => [
                self::EXP_EN_TRAMITE,
            ],

            self::EXP_EN_TRAMITE => [
                self::EXP_CERRADO,
            ],

            self::EXP_CERRADO => [
                self::EXP_ARCHIVADO,
            ],

            self::EXP_ARCHIVADO => [],
        };
    }
    public function puedeCambiarA(self $nuevoEstado): bool
    {
        return in_array(
            $nuevoEstado,
            $this->transicionesPermitidas(),
            true
        );
    }
}
