<?php

namespace App\Services\Derivaciones;

use App\Models\Derivacion;
use App\Models\Documento;
use App\Models\Estado;
use App\Models\UserCargo;
use App\Services\Auditoria\AuditoriaService;
use App\Services\Movimientos\MovimientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AtencionDerivacionService
{
    private const RESULTADO_CONTINUAR = 'CONTINUAR';

    private const RESULTADO_DERIVAR = 'DERIVAR';

    private const RESULTADO_FINALIZAR = 'FINALIZAR';


    public function __construct(
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
        private readonly DerivacionService $derivacionService,
    ) {}


    public function atender(
        Derivacion $derivacion,
        int $atendidoPor,
        string $resultado = self::RESULTADO_CONTINUAR,
        ?array $nuevaDerivacion = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Derivacion {
        return DB::transaction(function () use (
            $derivacion,
            $atendidoPor,
            $resultado,
            $nuevaDerivacion,
            $ip,
            $userAgent
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. Bloquear derivación
            |--------------------------------------------------------------------------
            */

            $derivacion = Derivacion::query()
                ->whereKey($derivacion->id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 2. Bloquear documento
            |--------------------------------------------------------------------------
            */

            $documento = Documento::query()
                ->whereKey($derivacion->documento_id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | 3. Validar resultado solicitado
            |--------------------------------------------------------------------------
            */

            $this->validarResultado(
                $resultado
            );

            /*
            |--------------------------------------------------------------------------
            | 3.1 Validar datos requeridos para nueva derivación
            |--------------------------------------------------------------------------
            */

            $this->validarDatosNuevaDerivacion(
                resultado: $resultado,
                nuevaDerivacion: $nuevaDerivacion,
            );

            /*
            |--------------------------------------------------------------------------
            | 4. Obtener estados
            |--------------------------------------------------------------------------
            */

            $estadoRecibida =
                $this->obtenerEstadoDerivacion(
                    'DER_RECIBIDA'
                );

            $estadoAtendida =
                $this->obtenerEstadoDerivacion(
                    'DER_ATENDIDA'
                );


            /*
            |--------------------------------------------------------------------------
            | 5. Validar estado actual de derivación
            |--------------------------------------------------------------------------
            */

            $this->validarEstadoActual(
                derivacion: $derivacion,
                estadoRecibidaId: $estadoRecibida->id,
            );


            /*
            |--------------------------------------------------------------------------
            | 6. Validar fecha de recepción
            |--------------------------------------------------------------------------
            */

            if ($derivacion->fecha_recepcion === null) {
                throw ValidationException::withMessages([
                    'derivacion' =>
                    'La derivación debe ser recibida antes de poder ser atendida.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 7. Evitar atención duplicada
            |--------------------------------------------------------------------------
            */

            if ($derivacion->fecha_atencion !== null) {
                throw ValidationException::withMessages([
                    'derivacion' =>
                    'La derivación ya fue atendida anteriormente.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 8. Validar usuario que atiende
            |--------------------------------------------------------------------------
            */

            $this->validarUsuarioEnAreaDestino(
                userId: $atendidoPor,
                areaDestinoId: $derivacion->area_destino_id,
            );

            /*
            |--------------------------------------------------------------------------
            | 8.1 Validar responsable específico
            |--------------------------------------------------------------------------
            |
            | Si la derivación fue asignada directamente a un usuario, solamente
            | dicho usuario puede atenderla.
            |
            */

            $this->validarResponsableDestino(
                derivacion: $derivacion,
                atendidoPor: $atendidoPor,
            );


            /*
            |--------------------------------------------------------------------------
            | 9. Validar que el documento siga en el área destino
            |--------------------------------------------------------------------------
            */

            $this->validarDocumentoEnAreaDestino(
                documento: $documento,
                areaDestinoId: $derivacion->area_destino_id,
            );



            /*
            |--------------------------------------------------------------------------
            | 10. Guardar estados anteriores
            |--------------------------------------------------------------------------
            */

            $estadoAnteriorDerivacionId =
                $derivacion->estado_id;

            $estadoAnteriorDocumentoId =
                $documento->estado_id;


            /*
            |--------------------------------------------------------------------------
            | 11. Marcar derivación como atendida
            |--------------------------------------------------------------------------
            */

            $derivacion->update([
                'estado_id' =>
                $estadoAtendida->id,

                'fecha_atencion' =>
                now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | 12. Determinar qué ocurre con el documento
            |--------------------------------------------------------------------------
            */

            $estadoNuevoDocumentoId =
                $estadoAnteriorDocumentoId;

            if (
                $resultado === self::RESULTADO_FINALIZAR
            ) {

                /*
    |--------------------------------------------------------------------------
    | Verificar otras derivaciones pendientes
    |--------------------------------------------------------------------------
    |
    | La derivación actual ya fue marcada como DER_ATENDIDA.
    | Ahora verificamos si el mismo documento conserva otras ramas
    | pendientes, enviadas o recibidas.
    |
    */

                $estadosPendientes = Estado::query()
                    ->where('ambito', 'DERIVACION')
                    ->whereIn('codigo', [
                        'DER_PENDIENTE',
                        'DER_ENVIADA',
                        'DER_RECIBIDA',
                    ])
                    ->pluck('id');

                $existenOtrasDerivacionesActivas = Derivacion::query()
                    ->where('documento_id', $documento->id)
                    ->where('id', '!=', $derivacion->id)
                    ->whereIn('estado_id', $estadosPendientes)
                    ->exists();

                /*
                |--------------------------------------------------------------------------
                | Finalizar documento únicamente si no quedan ramas activas
                |--------------------------------------------------------------------------
                */

                if (! $existenOtrasDerivacionesActivas) {

                    $estadoDocumentoAtendido =
                        $this->obtenerEstadoDocumento(
                            'DOC_ATENDIDO'
                        );

                    $documento->update([
                        'estado_id' =>
                        $estadoDocumentoAtendido->id,
                    ]);

                    $estadoNuevoDocumentoId =
                        $estadoDocumentoAtendido->id;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 12.1 Crear nueva derivación cuando el resultado sea DERIVAR
            |--------------------------------------------------------------------------
            |
            | La derivación anterior ya quedó DER_ATENDIDA.
            |
            | El nuevo origen será el área que acaba de atender el documento,
            | es decir, el área destino de la derivación anterior.
            |
            */

            if ($resultado === self::RESULTADO_DERIVAR) {

                $datosNuevaDerivacion = [
                    'area_origen_id' =>
                    $derivacion->area_destino_id,

                    'area_destino_id' =>
                    (int) $nuevaDerivacion['area_destino_id'],

                    'derivado_por' =>
                    $atendidoPor,

                    'responsable_destino_id' =>
                    $nuevaDerivacion['responsable_destino_id'] ?? null,

                    'indicacion' =>
                    $nuevaDerivacion['indicacion'] ?? null,

                    'fecha_limite' =>
                    $nuevaDerivacion['fecha_limite'] ?? null,

                    'permitir_multiples' =>
                    false,

                    'archivos' =>
                    $nuevaDerivacion['archivos'] ?? [],

                    'ip' =>
                    $ip,

                    'user_agent' =>
                    $userAgent,
                ];

                $this->derivacionService->derivar(
                    documento: $documento->fresh(),
                    datos: $datosNuevaDerivacion,
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 13. Registrar movimiento de atención
            |--------------------------------------------------------------------------
            */

            $this->movimientoService
                ->registrarAtencionDerivacion(
                    documento: $documento,
                    derivacion: $derivacion,
                    userId: $atendidoPor,
                    areaId: $derivacion->area_destino_id,
                    estadoAnteriorId: $estadoAnteriorDerivacionId,
                    estadoNuevoId: $estadoAtendida->id,
                    resultado: $resultado,
                );


            /*
            |--------------------------------------------------------------------------
            | 14. Si finaliza documento, registrar cambio documental
            |--------------------------------------------------------------------------
            */

            if (
                $estadoAnteriorDocumentoId
                !==
                $estadoNuevoDocumentoId
            ) {
                $this->movimientoService
                    ->registrarDocumentoAtendido(
                        documento: $documento,
                        userId: $atendidoPor,
                        areaId: $derivacion->area_destino_id,
                        estadoAnteriorId: $estadoAnteriorDocumentoId,
                        estadoNuevoId: $estadoNuevoDocumentoId,
                        derivacionId: $derivacion->id,
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | 15. Auditoría
            |--------------------------------------------------------------------------
            */

            $this->auditoriaService
                ->registrarAtencionDerivacion(
                    documento: $documento,
                    derivacion: $derivacion,
                    resultado: $resultado,
                    estadoAnteriorDerivacionId: $estadoAnteriorDerivacionId,
                    estadoAnteriorDocumentoId: $estadoAnteriorDocumentoId,
                    userId: $atendidoPor,
                    ip: $ip,
                    userAgent: $userAgent,
                );


            return $derivacion->fresh();
        }, 3);
    }


    private function validarResultado(
        string $resultado
    ): void {
        if (! in_array(
            $resultado,
            [
                self::RESULTADO_CONTINUAR,
                self::RESULTADO_DERIVAR,
                self::RESULTADO_FINALIZAR,
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'resultado' =>
                'El resultado de atención indicado no es válido.',
            ]);
        }
    }

    private function validarDatosNuevaDerivacion(
        string $resultado,
        ?array $nuevaDerivacion
    ): void {

        /*
    |--------------------------------------------------------------------------
    | Si no se solicitó DERIVAR, no necesitamos destino nuevo
    |--------------------------------------------------------------------------
    */

        if ($resultado !== self::RESULTADO_DERIVAR) {
            return;
        }

        /*
    |--------------------------------------------------------------------------
    | DERIVAR exige información de continuación
    |--------------------------------------------------------------------------
    */

        if ($nuevaDerivacion === null) {
            throw ValidationException::withMessages([
                'nueva_derivacion' =>
                'Debe indicar los datos de la nueva derivación.',
            ]);
        }

        if (
            ! isset($nuevaDerivacion['area_destino_id']) ||
            empty($nuevaDerivacion['area_destino_id'])
        ) {
            throw ValidationException::withMessages([
                'area_destino_id' =>
                'Debe indicar el área destino de la nueva derivación.',
            ]);
        }
    }


    private function obtenerEstadoDerivacion(
        string $codigo
    ): Estado {
        $estado = Estado::query()
            ->where('codigo', $codigo)
            ->where('ambito', 'DERIVACION')
            ->first();

        if (! $estado) {
            throw ValidationException::withMessages([
                'estado_derivacion' =>
                "No se encuentra configurado el estado {$codigo}.",
            ]);
        }

        return $estado;
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
        Derivacion $derivacion,
        int $estadoRecibidaId
    ): void {
        if (
            (int) $derivacion->estado_id
            !==
            (int) $estadoRecibidaId
        ) {
            $estadoActual = Estado::find(
                $derivacion->estado_id
            );

            throw ValidationException::withMessages([
                'derivacion' =>
                sprintf(
                    'La derivación no puede ser atendida porque actualmente se encuentra en estado %s.',
                    $estadoActual?->codigo ?? 'DESCONOCIDO'
                ),
            ]);
        }
    }


    private function validarUsuarioEnAreaDestino(
        int $userId,
        int $areaDestinoId
    ): void {
        $asignacion = UserCargo::query()
            ->where('user_id', $userId)
            ->where('area_id', $areaDestinoId)
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
                'atendido_por' =>
                'El usuario no pertenece actualmente al área destino de la derivación.',
            ]);
        }
    }

    private function validarResponsableDestino(
        Derivacion $derivacion,
        int $atendidoPor
    ): void {

        if (
            $derivacion->responsable_destino_id !== null &&
            (int) $derivacion->responsable_destino_id !== (int) $atendidoPor
        ) {
            throw ValidationException::withMessages([
                'atendido_por' =>
                'La derivación está asignada a otro usuario responsable.',
            ]);
        }
    }


    private function validarDocumentoEnAreaDestino(
        Documento $documento,
        int $areaDestinoId
    ): void {

        /*
    |--------------------------------------------------------------------------
    | 1. Documento con una única ubicación
    |--------------------------------------------------------------------------
    */

        if (
            $documento->area_actual_id !== null &&
            (int) $documento->area_actual_id === (int) $areaDestinoId
        ) {
            return;
        }

        /*
    |--------------------------------------------------------------------------
    | 2. Documento con múltiples destinos
    |--------------------------------------------------------------------------
    |
    | area_actual_id = NULL significa que la ubicación debe determinarse
    | mediante las derivaciones activas.
    |
    */

        if ($documento->area_actual_id === null) {

            $estadosActivos = Estado::query()
                ->where('ambito', 'DERIVACION')
                ->whereIn('codigo', [
                    'DER_PENDIENTE',
                    'DER_ENVIADA',
                    'DER_RECIBIDA',
                ])
                ->pluck('id');

            $existeDerivacionActiva = Derivacion::query()
                ->where('documento_id', $documento->id)
                ->where('area_destino_id', $areaDestinoId)
                ->whereIn('estado_id', $estadosActivos)
                ->exists();

            if ($existeDerivacionActiva) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'documento' =>
            'El documento ya no se encuentra disponible en el área responsable de esta derivación.',
        ]);
    }
}
