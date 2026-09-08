<?php

namespace App\Services\Documentos;


use App\Models\Documento;
use App\Models\DocumentoExterno;
use App\Models\Estado;
use App\Models\RemitenteExterno;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use App\Services\Archivos\ArchivoDocumentoService;
use App\Services\Movimientos\MovimientoService;

use App\Services\Auditoria\AuditoriaService;

class DocumentoExternoService
{
    public function __construct(
        private readonly ArchivoDocumentoService $archivoDocumentoService,
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {}
    /**
     * Registra un documento externo completo.
     *
     * En esta primera versión:
     * - Obtiene el estado inicial.
     * - Genera el código del expediente.
     * - Registra o reutiliza al remitente externo.
     * - Crea el documento.
     * - Crea el detalle de documento externo.
     *
     * Todavía no incluye:
     * - Archivos.
     * - Movimientos.
     * - Auditoría.
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
            | 1. Obtener estado inicial
            |--------------------------------------------------------------------------
            */

                $estado = $this->obtenerEstadoInicial();


                /*
            |--------------------------------------------------------------------------
            | 2. Generar código
            |--------------------------------------------------------------------------
            */

                $codigo = $this->generarCodigo();


                /*
            |--------------------------------------------------------------------------
            | 3. Registrar remitente
            |--------------------------------------------------------------------------
            */

                $remitente = $this->registrarRemitenteExterno(
                    $datos['remitente']
                );


                /*
            |--------------------------------------------------------------------------
            | 4. Crear documento
            |--------------------------------------------------------------------------
            */

                $documento = $this->crearDocumento(
                    datos: $datos,
                    estado: $estado,
                    codigo: $codigo,
                );


                /*
            |--------------------------------------------------------------------------
            | 5. Crear documento externo
            |--------------------------------------------------------------------------
            */

                $documentoExterno = $this->crearDocumentoExterno(
                    datos: $datos,
                    documento: $documento,
                    remitente: $remitente,
                );


                /*
            |--------------------------------------------------------------------------
            | 6. Guardar archivos
            |--------------------------------------------------------------------------
            */

                if (! empty($datos['archivos'])) {

                    $archivosGuardados =
                        $this->archivoDocumentoService
                        ->guardarArchivos(
                            documento: $documento,
                            archivos: $datos['archivos'],
                            subidoPor: $datos['registrado_por'] ?? null,
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
                    ->registrarDocumentoExterno(
                        documento: $documento,
                        documentoExterno: $documentoExterno,
                        userId: $datos['registrado_por'] ?? null,
                        areaId: $documento->area_actual_id,
                    );

                /*
                |--------------------------------------------------------------------------
                | 8. Registrar auditoría
                |--------------------------------------------------------------------------
                */

                $this->auditoriaService
                    ->registrarDocumentoExterno(
                        documento: $documento,
                        documentoExterno: $documentoExterno,
                        remitente: $remitente,
                        userId: $datos['registrado_por'] ?? null,
                        ip: $datos['ip_registro'] ?? null,
                        userAgent: $datos['user_agent'] ?? null,
                    );


                /*
            |--------------------------------------------------------------------------
            | 9. Retornar documento
            |--------------------------------------------------------------------------
            */



                return $documento->fresh();
            }, 3);
        } catch (Throwable $e) {

            /*
        |--------------------------------------------------------------------------
        | La BD hace rollback.
        | Nosotros eliminamos cualquier archivo físico que haya quedado.
        |--------------------------------------------------------------------------
        */

            $this->archivoDocumentoService
                ->eliminarArchivosFisicos(
                    $rutasGuardadas
                );

            throw $e;
        }
    }


    /**
     * Obtiene el estado inicial correspondiente
     * a un documento recién registrado.
     */
    private function obtenerEstadoInicial(): Estado
    {
        $estado = Estado::query()
            ->where('codigo', 'DOC_REGISTRADO')
            ->where('ambito', 'DOCUMENTO')
            ->first();

        if (! $estado) {
            throw ValidationException::withMessages([
                'estado' => 'No se encuentra configurado el estado DOC_REGISTRADO.',
            ]);
        }

        return $estado;
    }


    /**
     * Genera el código correlativo del expediente.
     *
     * Ejemplo:
     * EXP-2026-000001
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
     * Registra o actualiza un remitente externo.
     *
     * Se identifica principalmente por:
     * tipo_documento_identidad + numero_documento_identidad.
     */
    private function registrarRemitenteExterno(
        array $datos
    ): RemitenteExterno {

        return RemitenteExterno::updateOrCreate(
            [
                'tipo_documento_identidad' =>
                $datos['tipo_documento_identidad'],

                'numero_documento_identidad' =>
                $datos['numero_documento_identidad'],
            ],
            [
                'tipo_persona' =>
                $datos['tipo_persona'],

                'nombres' =>
                $datos['nombres'] ?? null,

                'apellidos' =>
                $datos['apellidos'] ?? null,

                'razon_social' =>
                $datos['razon_social'] ?? null,

                'correo' =>
                $datos['correo'] ?? null,

                'telefono' =>
                $datos['telefono'] ?? null,

                'direccion' =>
                $datos['direccion'] ?? null,

                'ubigeo' =>
                $datos['ubigeo'] ?? null,
            ]
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

            'origen' => 'EXTERNO',

            'tipo_documento_id' =>
            $datos['tipo_documento_id'],

            'estado_id' =>
            $estado->id,

            'prioridad_id' =>
            $datos['prioridad_id'] ?? null,

            'area_actual_id' =>
            $datos['area_actual_id'] ?? null,

            'registrado_por' =>
            $datos['registrado_por'] ?? null,

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
            $datos['fecha_documento'] ?? null,

            'fecha_registro' =>
            now(),

            'fecha_limite' =>
            $datos['fecha_limite'] ?? null,

            'confidencial' =>
            $datos['confidencial'] ?? false,

            'requiere_respuesta' =>
            $datos['requiere_respuesta'] ?? true,
        ]);
    }


    /**
     * Registra los datos propios de un documento externo.
     */
    private function crearDocumentoExterno(
        array $datos,
        Documento $documento,
        RemitenteExterno $remitente
    ): DocumentoExterno {

        return DocumentoExterno::create([
            'documento_id' =>
            $documento->id,

            'remitente_externo_id' =>
            $remitente->id,

            'canal_ingreso' =>
            $datos['canal_ingreso'] ?? 'VIRTUAL',

            'codigo_seguimiento' =>
            $this->generarCodigoSeguimiento(),

            'ip_registro' =>
            $datos['ip_registro'] ?? null,

            'user_agent' =>
            $datos['user_agent'] ?? null,

            'fecha_recepcion' =>
            $datos['fecha_recepcion'] ?? null,

            'recepcionado_por' =>
            $datos['recepcionado_por'] ?? null,

            'observacion_recepcion' =>
            $datos['observacion_recepcion'] ?? null,
        ]);
    }


    /**
     * Genera un código público de seguimiento.
     *
     * Este código no reemplaza al código del expediente.
     */
    private function generarCodigoSeguimiento(): string
    {
        do {
            $codigo = strtoupper(
                substr(bin2hex(random_bytes(8)), 0, 12)
            );
        } while (
            DocumentoExterno::where(
                'codigo_seguimiento',
                $codigo
            )->exists()
        );

        return $codigo;
    }
}
