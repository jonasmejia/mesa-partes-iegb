<?php

namespace App\Services\Documentos;

use App\Models\Documento;
use App\Models\DocumentoInterno;
use App\Models\Estado;
use App\Models\UserCargo;
use App\Services\Archivos\ArchivoDocumentoService;
use App\Services\Auditoria\AuditoriaService;
use App\Services\Movimientos\MovimientoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DocumentoInternoService
{
    public function __construct(
        private readonly ArchivoDocumentoService $archivoDocumentoService,
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {}

    /**
     * Registra el nacimiento de un documento interno.
     *
     * En esta etapa:
     * - valida al emisor;
     * - obtiene el estado inicial;
     * - genera el código interno del sistema;
     * - crea documentos;
     * - crea documentos_internos;
     * - guarda archivos;
     * - registra movimiento;
     * - registra auditoría.
     *
     * Las derivaciones/destinatarios se implementarán
     * después de validar este bloque.
     *
     * @throws Throwable
     */
    public function registrar(array $datos): Documento
    {
        $rutasGuardadas = [];

        try {
            return DB::transaction(function () use (
                $datos,
                &$rutasGuardadas
            ) {

                /*
                |--------------------------------------------------------------------------
                | 1. Validar emisor y área de origen
                |--------------------------------------------------------------------------
                */

                $this->validarEmisorEnArea(
                    userId: $datos['emisor_user_id'],
                    areaId: $datos['area_origen_id'],
                );

                /*
                |--------------------------------------------------------------------------
                | 2. Obtener estado inicial
                |--------------------------------------------------------------------------
                */

                $estado = $this->obtenerEstadoInicial();

                /*
                |--------------------------------------------------------------------------
                | 3. Generar código interno del sistema
                |--------------------------------------------------------------------------
                */

                $codigo = $this->generarCodigo();

                /*
                |--------------------------------------------------------------------------
                | 4. Crear documento principal
                |--------------------------------------------------------------------------
                */

                $documento = $this->crearDocumento(
                    datos: $datos,
                    estado: $estado,
                    codigo: $codigo,
                );

                /*
                |--------------------------------------------------------------------------
                | 5. Crear detalle del documento interno
                |--------------------------------------------------------------------------
                */

                $documentoInterno = $this->crearDocumentoInterno(
                    datos: $datos,
                    documento: $documento,
                );

                /*
                |--------------------------------------------------------------------------
                | 6. Guardar archivos del documento
                |--------------------------------------------------------------------------
                */

                if (! empty($datos['archivos'])) {

                    $archivosGuardados =
                        $this->archivoDocumentoService
                            ->guardarArchivos(
                                documento: $documento,
                                archivos: $datos['archivos'],
                                subidoPor: $datos['emisor_user_id'],
                            );

                    foreach ($archivosGuardados as $archivo) {
                        $rutasGuardadas[] = $archivo->ruta;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | 7. Registrar movimiento inicial
                |--------------------------------------------------------------------------
                */

                $this->movimientoService
                    ->registrarDocumentoInterno(
                        documento: $documento,
                        documentoInterno: $documentoInterno,
                        userId: $datos['emisor_user_id'],
                        areaId: $datos['area_origen_id'],
                    );

                /*
                |--------------------------------------------------------------------------
                | 8. Registrar auditoría
                |--------------------------------------------------------------------------
                */

                $this->auditoriaService
                    ->registrarDocumentoInterno(
                        documento: $documento,
                        documentoInterno: $documentoInterno,
                        userId: $datos['emisor_user_id'],
                        ip: $datos['ip'] ?? null,
                        userAgent: $datos['user_agent'] ?? null,
                    );

                /*
                |--------------------------------------------------------------------------
                | 9. Retornar documento
                |--------------------------------------------------------------------------
                */

                return $documento->fresh([
                    'documentoInterno',
                    'archivos',
                    'movimientos',
                ]);
            }, 3);

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | La transacción revierte la BD.
            | Eliminamos archivos físicos que hayan quedado almacenados.
            |--------------------------------------------------------------------------
            */

            $this->archivoDocumentoService
                ->eliminarArchivosFisicos($rutasGuardadas);

            throw $e;
        }
    }

    /**
     * Comprueba que el usuario emisor tenga una asignación
     * activa y vigente dentro del área indicada.
     */
    private function validarEmisorEnArea(
        int $userId,
        int $areaId
    ): void {

        $existe = UserCargo::query()
            ->where('user_id', $userId)
            ->where('area_id', $areaId)
            ->asignacionesActuales()
            ->exists();

        if (! $existe) {
            throw ValidationException::withMessages([
                'emisor_user_id' =>
                    'El usuario emisor no tiene una asignación activa y vigente en el área de origen indicada.',
            ]);
        }
    }

    /**
     * Obtiene el estado inicial de un documento interno.
     */
    private function obtenerEstadoInicial(): Estado
    {
        $estado = Estado::query()
            ->where('codigo', 'DOC_REGISTRADO')
            ->where('ambito', 'DOCUMENTO')
            ->first();

        if (! $estado) {
            throw ValidationException::withMessages([
                'estado' =>
                    'No se encuentra configurado el estado DOC_REGISTRADO.',
            ]);
        }

        return $estado;
    }

    /**
     * Genera el código interno general del sistema.
     *
     * Ejemplo:
     * EXP-2026-000001
     *
     * Este código NO corresponde al número formal
     * del memorándum, informe, oficio, etc.
     */
    private function generarCodigo(): string
    {
        $anio = now()->year;

        $ultimoDocumento = Documento::query()
            ->where('anio', $anio)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $correlativo = 1;

        if ($ultimoDocumento) {

            $partes = explode('-', $ultimoDocumento->codigo);

            $ultimoCorrelativo = (int) end($partes);

            $correlativo = $ultimoCorrelativo + 1;
        }

        return sprintf(
            'EXP-%d-%06d',
            $anio,
            $correlativo
        );
    }

    /**
     * Crea el registro principal en documentos.
     */
    private function crearDocumento(
        array $datos,
        Estado $estado,
        string $codigo
    ): Documento {

        return Documento::create([
            'codigo' => $codigo,

            'origen' => 'INTERNO',

            'tipo_documento_id' =>
                $datos['tipo_documento_id'],

            'estado_id' =>
                $estado->id,

            'prioridad_id' =>
                $datos['prioridad_id'],

            /*
             * Mientras el documento todavía no ha sido enviado,
             * se encuentra en el área que lo genera.
             */
            'area_actual_id' =>
                $datos['area_origen_id'],

            'registrado_por' =>
                $datos['emisor_user_id'],

            'numero_documento' =>
                $datos['numero_documento'] ?? null,

            'anio' =>
                now()->year,

            'sigla' =>
                $datos['sigla'] ?? null,

            'asunto' =>
                $datos['asunto'],

            'descripcion' =>
                $datos['descripcion'] ?? null,

            'folios' =>
                $datos['folios'] ?? 1,

            'fecha_documento' =>
                $datos['fecha_documento'] ?? now(),

            'fecha_registro' =>
                now(),

            'fecha_limite' =>
                $datos['fecha_limite'] ?? null,

            'confidencial' =>
                $datos['confidencial'] ?? false,

            'requiere_respuesta' =>
                $datos['requiere_respuesta'] ?? false,
        ]);
    }

    /**
     * Crea los datos específicos del documento interno.
     */
    private function crearDocumentoInterno(
        array $datos,
        Documento $documento
    ): DocumentoInterno {

        return DocumentoInterno::create([
            'documento_id' =>
                $documento->id,

            'area_origen_id' =>
                $datos['area_origen_id'],

            'emisor_user_id' =>
                $datos['emisor_user_id'],

            'requiere_firma' =>
                $datos['requiere_firma'] ?? false,

            'fecha_emision' =>
                $datos['fecha_emision'] ?? now(),
        ]);
    }
}