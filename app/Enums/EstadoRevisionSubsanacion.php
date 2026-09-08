<?php

namespace App\Enums;

enum EstadoRevisionSubsanacion: string
{
    case PENDIENTE = 'PENDIENTE';
    case EN_REVISION = 'EN_REVISION';
    case ACEPTADA = 'ACEPTADA';
    case RECHAZADA = 'RECHAZADA';

    /**
     * Retorna los estados a los que puede cambiar
     * el estado actual de la subsanación.
     */
    public function transicionesPermitidas(): array
    {
        return match ($this) {

            self::PENDIENTE => [
                self::EN_REVISION,
            ],

            self::EN_REVISION => [
                self::ACEPTADA,
                self::RECHAZADA,
            ],

            self::ACEPTADA,
            self::RECHAZADA => [],
        };
    }

    /**
     * Indica si el estado actual puede cambiar
     * al nuevo estado recibido.
     */
    public function puedeCambiarA(self $nuevoEstado): bool
    {
        return in_array(
            $nuevoEstado,
            $this->transicionesPermitidas(),
            true
        );
    }

    /**
     * Indica si el estado es final.
     */
    public function esFinal(): bool
    {
        return match ($this) {
            self::PENDIENTE => false,

            self::ACEPTADA,
            self::RECHAZADA => true,
        };
    }
}
