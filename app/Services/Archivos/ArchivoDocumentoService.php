<?php

namespace App\Services\Archivos;

use App\Models\Documento;
use App\Models\DocumentoArchivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ArchivoDocumentoService
{
    /**
     * Disco privado donde se almacenarán los documentos.
     */
    private string $disk = 'private';


    /**
     * Guarda varios archivos asociados a un documento.
     *
     * @param Documento $documento
     * @param array $archivos
     * @param int|null $subidoPor
     *
     * @return array<int, DocumentoArchivo>
     *
     * @throws Throwable
     */

    public function guardarArchivos(
        Documento $documento,
        array $archivos,
        ?int $subidoPor = null
    ): array {
        
        $this->validarArchivos($archivos);

        $archivosRegistrados = [];

        try {

            foreach ($archivos as $indice => $item) {

                /*
                |--------------------------------------------------------------------------
                | Formato esperado
                |--------------------------------------------------------------------------
                |
                | [
                |     'archivo' => UploadedFile,
                |     'tipo' => 'DOCUMENTO',
                |     'es_principal' => true,
                | ]
                |
                */

                if (
                    ! isset($item['archivo']) ||
                    ! $item['archivo'] instanceof UploadedFile
                ) {
                    throw new RuntimeException(
                        "El archivo en la posición {$indice} no es válido."
                    );
                }

                $archivosRegistrados[] = $this->guardarArchivo(
                    documento: $documento,
                    archivo: $item['archivo'],
                    tipo: $item['tipo'] ?? 'DOCUMENTO',
                    esPrincipal: (bool) ($item['es_principal'] ?? false),
                    subidoPor: $subidoPor,
                );
            }

            return $archivosRegistrados;
        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Eliminar archivos físicos creados durante esta operación
            |--------------------------------------------------------------------------
            */

            foreach ($archivosRegistrados as $archivoRegistrado) {
                $this->eliminarArchivoFisico(
                    $archivoRegistrado->ruta
                );
            }

            throw $e;
        }
    }


    /**
     * Guarda un archivo físico y registra sus metadatos en BD.
     */
    private function guardarArchivo(
        Documento $documento,
        UploadedFile $archivo,
        string $tipo,
        bool $esPrincipal,
        ?int $subidoPor = null
    ): DocumentoArchivo {

        /*
        |--------------------------------------------------------------------------
        | 1. Validar archivo
        |--------------------------------------------------------------------------
        */

        if (! $archivo->isValid()) {
            throw new RuntimeException(
                'El archivo recibido no es válido.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 2. Obtener información original
        |--------------------------------------------------------------------------
        */

        $nombreOriginal = $archivo->getClientOriginalName();

        $extension = strtolower(
            $archivo->getClientOriginalExtension()
        );

        $mimeType = $archivo->getMimeType();

        $tamanoBytes = $archivo->getSize();


        /*
        |--------------------------------------------------------------------------
        | 3. Generar nombre físico seguro
        |--------------------------------------------------------------------------
        */

        $nombreGuardado = $this->generarNombreGuardado(
            $extension
        );


        /*
        |--------------------------------------------------------------------------
        | 4. Construir carpeta
        |--------------------------------------------------------------------------
        */

        $directorio = sprintf(
            'documentos/%s/%s',
            $documento->anio,
            $documento->codigo
        );


        /*
        |--------------------------------------------------------------------------
        | 5. Guardar archivo físico
        |--------------------------------------------------------------------------
        */

        $ruta = $archivo->storeAs(
            $directorio,
            $nombreGuardado,
            $this->disk
        );

        if (! $ruta) {
            throw new RuntimeException(
                'No se pudo almacenar físicamente el archivo.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Generar SHA-256
        |--------------------------------------------------------------------------
        */

        try {

            $rutaAbsoluta = Storage::disk($this->disk)
                ->path($ruta);

            $hashSha256 = hash_file(
                'sha256',
                $rutaAbsoluta
            );

            if (! $hashSha256) {
                throw new RuntimeException(
                    'No se pudo generar el hash SHA-256 del archivo.'
                );
            }
        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Si falla el hash, eliminamos inmediatamente el archivo físico
            |--------------------------------------------------------------------------
            */

            $this->eliminarArchivoFisico($ruta);

            throw $e;
        }


        /*
        |--------------------------------------------------------------------------
        | 7. Registrar metadatos en base de datos
        |--------------------------------------------------------------------------
        */

        try {

            return DocumentoArchivo::create([
                'documento_id' => $documento->id,

                'tipo' => $tipo,

                'nombre_original' => $nombreOriginal,

                'nombre_guardado' => $nombreGuardado,

                'ruta' => $ruta,

                'mime_type' => $mimeType,

                'tamano_bytes' => $tamanoBytes,

                'hash_sha256' => $hashSha256,

                'subido_por' => $subidoPor,

                'es_principal' => $esPrincipal,
            ]);
        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Si falla la BD, eliminamos el archivo físico
            |--------------------------------------------------------------------------
            */

            $this->eliminarArchivoFisico($ruta);

            throw $e;
        }
    }


    /**
     * Genera un nombre físico único para el archivo.
     */
    private function generarNombreGuardado(
        string $extension
    ): string {

        $nombre = bin2hex(random_bytes(16));

        if ($extension !== '') {
            return $nombre . '.' . $extension;
        }

        return $nombre;
    }


    /**
     * Elimina un archivo físico.
     */
    public function eliminarArchivoFisico(
        ?string $ruta
    ): void {

        if (! $ruta) {
            return;
        }

        if (
            Storage::disk($this->disk)
            ->exists($ruta)
        ) {
            Storage::disk($this->disk)
                ->delete($ruta);
        }
    }


    /**
     * Elimina varios archivos físicos.
     *
     * Útil cuando una operación global falla y la BD realiza rollback.
     */
    public function eliminarArchivosFisicos(
        array $rutas
    ): void {

        foreach ($rutas as $ruta) {
            $this->eliminarArchivoFisico($ruta);
        }
    }

    private function validarArchivos(
        array $archivos
    ): void {

        if (empty($archivos)) {
            throw new RuntimeException(
                'El documento debe contener al menos un archivo.'
            );
        }

        $principales = collect($archivos)
            ->filter(
                fn($item) =>
                (bool) ($item['es_principal'] ?? false)
            )
            ->count();

        if ($principales !== 1) {
            throw new RuntimeException(
                'Debe existir exactamente un archivo principal.'
            );
        }
    }
}
