<?php

namespace App\Enums;

enum EstadoAmbito: string
{
    case DOCUMENTO = 'DOCUMENTO';
    case DERIVACION = 'DERIVACION';
    case EXPEDIENTE = 'EXPEDIENTE';
    case SUBSANACION = 'SUBSANACION';
}