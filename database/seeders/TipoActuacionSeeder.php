<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoActuacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposActuacion = [
            [
                'codigo' => 'REVISION',
                'nombre' => 'Revisión',
                'descripcion' => 'Revisión del documento y de la información asociada al trámite.',
                'cierra_tramite' => false,
            ],
            [
                'codigo' => 'EVALUACION',
                'nombre' => 'Evaluación',
                'descripcion' => 'Evaluación administrativa, académica o técnica del documento recibido.',
                'cierra_tramite' => false,
            ],
            [
                'codigo' => 'INFORME',
                'nombre' => 'Emisión de informe',
                'descripcion' => 'Emisión de un informe como parte del proceso de evaluación o atención del trámite.',
                'cierra_tramite' => false,
            ],
            [
                'codigo' => 'RESPUESTA',
                'nombre' => 'Emisión de respuesta',
                'descripcion' => 'Emisión de una respuesta formal relacionada con el documento o trámite.',
                'cierra_tramite' => false,
            ],
            [
                'codigo' => 'APROBACION',
                'nombre' => 'Aprobación',
                'descripcion' => 'Registro de la aprobación del asunto sometido a evaluación.',
                'cierra_tramite' => false,
            ],
            [
                'codigo' => 'DENEGACION',
                'nombre' => 'Denegación',
                'descripcion' => 'Registro de la decisión mediante la cual no se aprueba lo solicitado.',
                'cierra_tramite' => false,
            ],
            [
                'codigo' => 'ATENCION',
                'nombre' => 'Atención',
                'descripcion' => 'Registro de una atención parcial o específica realizada respecto del documento.',
                'cierra_tramite' => false,
            ],
            [
                'codigo' => 'FINALIZACION',
                'nombre' => 'Finalización',
                'descripcion' => 'Conclusión de la atención sustantiva del trámite.',
                'cierra_tramite' => true,
            ],
            [
                'codigo' => 'ARCHIVAMIENTO',
                'nombre' => 'Archivamiento',
                'descripcion' => 'Cierre documental y archivo del trámite una vez concluida su atención.',
                'cierra_tramite' => true,
            ],
        ];

        foreach ($tiposActuacion as $tipo) {
            DB::table('tipos_actuacion')->updateOrInsert(
                [
                    'codigo' => $tipo['codigo'],
                ],
                [
                    'nombre' => $tipo['nombre'],
                    'descripcion' => $tipo['descripcion'],
                    'cierra_tramite' => $tipo['cierra_tramite'],
                    'activo' => true,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
