<?php

namespace App\Services\Archivos;

use App\Models\Derivacion;
use App\Models\DerivacionArchivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ArchivoDerivacionService
{
    private string $disk = 'private';

    public function guardarArchivos(
        Derivacion $derivacion,
        array $archivos,
        ?int $subidoPor = null
    ): array {
        $guardados = [];

        try {
            foreach ($archivos as $archivo) {
                if (! $archivo instanceof UploadedFile) {
                    throw ValidationException::withMessages([
                        'archivos' =>
                            'Uno de los archivos de derivación no es válido.',
                    ]);
                }

                $guardados[] = $this->guardarArchivo(
                    derivacion: $derivacion,
                    archivo: $archivo,
                    subidoPor: $subidoPor,
                );
            }

            return $guardados;

        } catch (Throwable $e) {
            foreach ($guardados as $guardado) {
                $this->eliminarArchivoFisico(
                    $guardado->ruta
                );
            }

            throw $e;
        }
    }

    private function guardarArchivo(
        Derivacion $derivacion,
        UploadedFile $archivo,
        ?int $subidoPor
    ): DerivacionArchivo {
        $nombreOriginal =
            $archivo->getClientOriginalName();

        $extension =
            strtolower(
                $archivo->getClientOriginalExtension()
            );

        $nombreGuardado =
            bin2hex(random_bytes(16));

        if ($extension !== '') {
            $nombreGuardado .= '.' . $extension;
        }

        $rutaDirectorio = sprintf(
            'derivaciones/%s/%s',
            now()->format('Y'),
            $derivacion->id
        );

        $ruta = $archivo->storeAs(
            $rutaDirectorio,
            $nombreGuardado,
            $this->disk
        );

        try {
            $rutaFisica = Storage::disk(
                $this->disk
            )->path($ruta);

            $hash = hash_file(
                'sha256',
                $rutaFisica
            );

            if ($hash === false) {
                throw new \RuntimeException(
                    'No fue posible calcular el hash SHA-256.'
                );
            }

            return DerivacionArchivo::create([
                'derivacion_id' =>
                    $derivacion->id,

                'nombre_original' =>
                    $nombreOriginal,

                'nombre_guardado' =>
                    $nombreGuardado,

                'ruta' =>
                    $ruta,

                'mime_type' =>
                    $archivo->getMimeType(),

                'tamano_bytes' =>
                    $archivo->getSize(),

                'hash_sha256' =>
                    $hash,

                'subido_por' =>
                    $subidoPor,
            ]);

        } catch (Throwable $e) {
            $this->eliminarArchivoFisico($ruta);

            throw $e;
        }
    }

    public function eliminarArchivoFisico(
        ?string $ruta
    ): void {
        if (
            $ruta &&
            Storage::disk($this->disk)->exists($ruta)
        ) {
            Storage::disk($this->disk)->delete($ruta);
        }
    }

    public function eliminarArchivosFisicos(
        array $rutas
    ): void {
        foreach ($rutas as $ruta) {
            $this->eliminarArchivoFisico($ruta);
        }
    }
}