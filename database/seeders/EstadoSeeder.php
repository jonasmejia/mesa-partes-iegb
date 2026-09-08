<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estados = [

            /*
            |--------------------------------------------------------------------------
            | ESTADOS DEL DOCUMENTO
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'DOC_REGISTRADO',
                'nombre' => 'Registrado',
                'ambito' => 'DOCUMENTO',
                'descripcion' => 'El documento ha sido registrado correctamente en el sistema.',
                'orden' => 10,
                'es_final' => false,
            ],
            [
                'codigo' => 'DOC_EN_TRAMITE',
                'nombre' => 'En trámite',
                'ambito' => 'DOCUMENTO',
                'descripcion' => 'El documento se encuentra siendo procesado por una o más áreas de la institución.',
                'orden' => 20,
                'es_final' => false,
            ],
            [
                'codigo' => 'DOC_OBSERVADO',
                'nombre' => 'Observado',
                'ambito' => 'DOCUMENTO',
                'descripcion' => 'El documento presenta observaciones que deben ser atendidas o subsanadas.',
                'orden' => 30,
                'es_final' => false,
            ],
            [
                'codigo' => 'DOC_ATENDIDO',
                'nombre' => 'Atendido',
                'ambito' => 'DOCUMENTO',
                'descripcion' => 'El documento ha sido atendido por el área responsable.',
                'orden' => 40,
                'es_final' => false,
            ],
            [
                'codigo' => 'DOC_ARCHIVADO',
                'nombre' => 'Archivado',
                'ambito' => 'DOCUMENTO',
                'descripcion' => 'El trámite del documento ha concluido y el documento ha sido archivado.',
                'orden' => 50,
                'es_final' => true,
            ],
            [
                'codigo' => 'DOC_ANULADO',
                'nombre' => 'Anulado',
                'ambito' => 'DOCUMENTO',
                'descripcion' => 'El documento ha sido anulado y ya no continúa dentro del flujo documentario.',
                'orden' => 60,
                'es_final' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | ESTADOS DE DERIVACIÓN
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'DER_PENDIENTE',
                'nombre' => 'Pendiente',
                'ambito' => 'DERIVACION',
                'descripcion' => 'La derivación ha sido generada y está pendiente de envío o procesamiento.',
                'orden' => 10,
                'es_final' => false,
            ],
            [
                'codigo' => 'DER_ENVIADA',
                'nombre' => 'Enviada',
                'ambito' => 'DERIVACION',
                'descripcion' => 'La derivación ha sido enviada al área o usuario destinatario y está pendiente de recepción.',
                'orden' => 20,
                'es_final' => false,
            ],
            [
                'codigo' => 'DER_RECIBIDA',
                'nombre' => 'Recibida',
                'ambito' => 'DERIVACION',
                'descripcion' => 'La derivación ha sido recibida por el área o usuario destinatario.',
                'orden' => 30,
                'es_final' => false,
            ],
            [
                'codigo' => 'DER_ATENDIDA',
                'nombre' => 'Atendida',
                'ambito' => 'DERIVACION',
                'descripcion' => 'La derivación ha sido atendida por el destinatario.',
                'orden' => 40,
                'es_final' => true,
            ],
            [
                'codigo' => 'DER_RECHAZADA',
                'nombre' => 'Rechazada',
                'ambito' => 'DERIVACION',
                'descripcion' => 'La recepción de la derivación ha sido rechazada de manera justificada.',
                'orden' => 50,
                'es_final' => true,
            ],
            [
                'codigo' => 'DER_CANCELADA',
                'nombre' => 'Cancelada',
                'ambito' => 'DERIVACION',
                'descripcion' => 'La derivación ha sido cancelada antes de completar su atención.',
                'orden' => 60,
                'es_final' => true,
            ],

            /*
|--------------------------------------------------------------------------
| ESTADOS DE SUBSANACIÓN
|--------------------------------------------------------------------------
*/

            [
                'codigo' => 'PENDIENTE',
                'nombre' => 'Pendiente',
                'ambito' => 'SUBSANACION',
                'descripcion' => 'La subsanación ha sido registrada y está pendiente de revisión.',
                'orden' => 10,
                'es_final' => false,
            ],
            [
                'codigo' => 'EN_REVISION',
                'nombre' => 'En revisión',
                'ambito' => 'SUBSANACION',
                'descripcion' => 'La subsanación se encuentra actualmente en proceso de revisión.',
                'orden' => 20,
                'es_final' => false,
            ],
            [
                'codigo' => 'ACEPTADA',
                'nombre' => 'Aceptada',
                'ambito' => 'SUBSANACION',
                'descripcion' => 'La subsanación fue revisada y aceptada satisfactoriamente.',
                'orden' => 30,
                'es_final' => true,
            ],
            [
                'codigo' => 'RECHAZADA',
                'nombre' => 'Rechazada',
                'ambito' => 'SUBSANACION',
                'descripcion' => 'La subsanación fue revisada y rechazada por no levantar satisfactoriamente la observación.',
                'orden' => 40,
                'es_final' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | ESTADOS DEL EXPEDIENTE
            |--------------------------------------------------------------------------
            */

            [
                'codigo' => 'EXP_ABIERTO',
                'nombre' => 'Abierto',
                'ambito' => 'EXPEDIENTE',
                'descripcion' => 'El expediente se encuentra abierto y puede continuar recibiendo actuaciones o documentos.',
                'orden' => 10,
                'es_final' => false,
            ],
            [
                'codigo' => 'EXP_EN_TRAMITE',
                'nombre' => 'En trámite',
                'ambito' => 'EXPEDIENTE',
                'descripcion' => 'El expediente se encuentra actualmente en proceso de atención.',
                'orden' => 20,
                'es_final' => false,
            ],
            [
                'codigo' => 'EXP_CERRADO',
                'nombre' => 'Cerrado',
                'ambito' => 'EXPEDIENTE',
                'descripcion' => 'El expediente ha concluido su proceso de atención.',
                'orden' => 30,
                'es_final' => true,
            ],
            [
                'codigo' => 'EXP_ARCHIVADO',
                'nombre' => 'Archivado',
                'ambito' => 'EXPEDIENTE',
                'descripcion' => 'El expediente cerrado ha sido archivado.',
                'orden' => 40,
                'es_final' => true,
            ],

        ];

        foreach ($estados as $estado) {

            $existe = DB::table('estados')
                ->where('codigo', $estado['codigo'])
                ->exists();

            DB::table('estados')->updateOrInsert(
                [
                    'codigo' => $estado['codigo'],
                ],
                [
                    'nombre' => $estado['nombre'],
                    'ambito' => $estado['ambito'],
                    'descripcion' => $estado['descripcion'],
                    'orden' => $estado['orden'],
                    'es_final' => $estado['es_final'],
                    'activo' => true,

                    'created_at' => $existe
                        ? DB::raw('created_at')
                        : now(),

                    'updated_at' => now(),
                ]
            );
        }
    }
}
