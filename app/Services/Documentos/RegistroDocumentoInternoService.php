<?php

namespace App\Services\Documentos;

use App\Models\Documento;
use App\Models\DocumentoInterno;
use App\Models\Estado;
use App\Services\Archivos\ArchivoDocumentoService;
use App\Services\Movimientos\MovimientoService;
use App\Services\Auditoria\AuditoriaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * El usuario emisor debe tener una asignación activa
 *en user_cargos para el area_origen_id indicado.
 */

use App\Models\User;
use App\Models\UserCargo;

class RegistroDocumentoInternoService
{
    public function __construct(
        private readonly ArchivoDocumentoService $archivoDocumentoService,
        private readonly MovimientoService $movimientoService,
        private readonly AuditoriaService $auditoriaService,
    ) {}

   

    /**
     * Registra un documento interno completo.
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
                | Validar usuario emisor
                |--------------------------------------------------------------------------
                */

                $this->validarUsuarioEmisor(
                    $datos['emisor_user_id']
                );


                /*
                |--------------------------------------------------------------------------
                | Validar pertenencia del emisor al área
                |--------------------------------------------------------------------------
                */

                $this->validarEmisorEnArea(
                    userId: $datos['emisor_user_id'],
                    areaId: $datos['area_origen_id'],
                );


                /*
                |--------------------------------------------------------------------------
                | 2. Generar código del expediente
                |--------------------------------------------------------------------------
                */

                $codigo = $this->generarCodigo();


                /*
                |--------------------------------------------------------------------------
                | 3. Crear documento
                |--------------------------------------------------------------------------
                */

                $documento = $this->crearDocumento(
                    datos: $datos,
                    estado: $estado,
                    codigo: $codigo,
                );


                /*
                |--------------------------------------------------------------------------
                | 4. Crear detalle interno
                |--------------------------------------------------------------------------
                */

                $documentoInterno = $this->crearDocumentoInterno(
                    datos: $datos,
                    documento: $documento,
                );


                /*
                |--------------------------------------------------------------------------
                | 5. Guardar archivos
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
                | 6. Registrar movimiento inicial
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
                | 7. Registrar auditoría
                |--------------------------------------------------------------------------
                */

                $this->auditoriaService
                    ->registrarDocumentoInterno(
                        documento: $documento,
                        documentoInterno: $documentoInterno,
                        userId: $datos['emisor_user_id'],
                        ip: $datos['ip_registro'] ?? null,
                        userAgent: $datos['user_agent'] ?? null,
                    );


                /*
                |--------------------------------------------------------------------------
                | 8. Retornar documento
                |--------------------------------------------------------------------------
                */

                return $documento->fresh();
            }, 3);
        } catch (Throwable $e) {

            $this->archivoDocumentoService
                ->eliminarArchivosFisicos(
                    $rutasGuardadas
                );

            throw $e;
        }
    }

    /**
     * Valida que el usuario emisor exista.
     *
     * @throws ValidationException
     */
    private function validarUsuarioEmisor(
        int $userId
    ): void {

        $existe = User::query()
            ->whereKey($userId)
            ->exists();

        if (! $existe) {
            throw ValidationException::withMessages([
                'emisor_user_id' =>
                'El usuario emisor seleccionado no existe.',
            ]);
        }
    }

     /**
     * Valida que el usuario emisor pertenezca al área de origen.
     *
     * @throws ValidationException
     */
    private function validarEmisorEnArea(
        int $userId,
        int $areaId
    ): void {

        $asignacion = UserCargo::query()
            ->where('user_id', $userId)
            ->where('area_id', $areaId)
            ->where('activo', true)
            ->whereDate('fecha_inicio', '<=', now()->toDateString())
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
                'area_origen_id' =>
                'El usuario emisor no pertenece actualmente al área de origen seleccionada.',
            ]);
        }
    }


    /**
     * Obtiene el estado inicial del documento.
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
     * Genera el código institucional.
     *
     * Por ahora conserva la misma estrategia del documento externo.
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

            $partes = explode(
                '-',
                $ultimoDocumento->codigo
            );

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
     * Crea el documento principal.
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
            $datos['prioridad_id'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | En el nacimiento del documento interno, el área actual
            | es el área de origen.
            |--------------------------------------------------------------------------
            */

            'area_actual_id' =>
            $datos['area_origen_id'],

            /*
            |--------------------------------------------------------------------------
            | El usuario que registra es el emisor.
            |--------------------------------------------------------------------------
            */

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
            $datos['fecha_documento'] ?? null,

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
     * Crea la información específica del documento interno.
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
