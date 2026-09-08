<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoDocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposDocumento = [
            [
                'codigo' => 'SOLICITUD',
                'nombre' => 'Solicitud',
                'ambito' => 'AMBOS',
                'descripcion' => 'Documento mediante el cual una persona solicita atención, autorización, información o la realización de un trámite.',
                'requiere_numero' => false,
            ],
            [
                'codigo' => 'OFICIO',
                'nombre' => 'Oficio',
                'ambito' => 'AMBOS',
                'descripcion' => 'Documento formal utilizado para comunicaciones oficiales entre dependencias, instituciones o autoridades.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'OFICIO_MULTIPLE',
                'nombre' => 'Oficio Múltiple',
                'ambito' => 'INTERNO',
                'descripcion' => 'Documento oficial dirigido simultáneamente a varios destinatarios.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'MEMORANDO',
                'nombre' => 'Memorando',
                'ambito' => 'INTERNO',
                'descripcion' => 'Documento de comunicación interna utilizado entre áreas, jefaturas o servidores de la institución.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'MEMORANDO_MULTIPLE',
                'nombre' => 'Memorando Múltiple',
                'ambito' => 'INTERNO',
                'descripcion' => 'Documento de comunicación interna dirigido a varios destinatarios.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'INFORME',
                'nombre' => 'Informe',
                'ambito' => 'INTERNO',
                'descripcion' => 'Documento mediante el cual se comunica información, análisis, resultados, conclusiones o recomendaciones.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'INFORME_TECNICO',
                'nombre' => 'Informe Técnico',
                'ambito' => 'INTERNO',
                'descripcion' => 'Documento técnico que contiene análisis especializado, evaluación, conclusiones y recomendaciones.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'CARTA',
                'nombre' => 'Carta',
                'ambito' => 'AMBOS',
                'descripcion' => 'Documento utilizado para comunicaciones formales con personas naturales o jurídicas.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'CARTA_NOTARIAL',
                'nombre' => 'Carta Notarial',
                'ambito' => 'EXTERNO',
                'descripcion' => 'Comunicación formal cursada mediante servicio notarial.',
                'requiere_numero' => false,
            ],
            [
                'codigo' => 'RESOLUCION',
                'nombre' => 'Resolución',
                'ambito' => 'INTERNO',
                'descripcion' => 'Documento administrativo que formaliza una decisión de la autoridad competente.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'ACTA',
                'nombre' => 'Acta',
                'ambito' => 'INTERNO',
                'descripcion' => 'Documento que deja constancia de hechos, acuerdos, reuniones o actuaciones realizadas.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'CONSTANCIA',
                'nombre' => 'Constancia',
                'ambito' => 'AMBOS',
                'descripcion' => 'Documento que acredita o certifica un hecho, situación o condición determinada.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'CERTIFICADO',
                'nombre' => 'Certificado',
                'ambito' => 'AMBOS',
                'descripcion' => 'Documento oficial mediante el cual se certifica determinada información o condición.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'CIRCULAR',
                'nombre' => 'Circular',
                'ambito' => 'INTERNO',
                'descripcion' => 'Documento de comunicación institucional dirigido a múltiples destinatarios.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'PROVEIDO',
                'nombre' => 'Proveído',
                'ambito' => 'INTERNO',
                'descripcion' => 'Documento o anotación administrativa mediante la cual se dispone la realización de una acción.',
                'requiere_numero' => true,
            ],
            [
                'codigo' => 'FORMATO',
                'nombre' => 'Formato',
                'ambito' => 'AMBOS',
                'descripcion' => 'Documento estructurado utilizado para registrar o presentar información específica.',
                'requiere_numero' => false,
            ],
            [
                'codigo' => 'DECLARACION_JURADA',
                'nombre' => 'Declaración Jurada',
                'ambito' => 'AMBOS',
                'descripcion' => 'Documento mediante el cual una persona declara información bajo responsabilidad.',
                'requiere_numero' => false,
            ],
            [
                'codigo' => 'EXPEDIENTE',
                'nombre' => 'Expediente',
                'ambito' => 'EXTERNO',
                'descripcion' => 'Conjunto documental presentado para iniciar o sustentar un trámite administrativo.',
                'requiere_numero' => false,
            ],
            [
                'codigo' => 'OTRO',
                'nombre' => 'Otro',
                'ambito' => 'AMBOS',
                'descripcion' => 'Tipo de documento no contemplado específicamente en el catálogo.',
                'requiere_numero' => false,
            ],
        ];

        foreach ($tiposDocumento as $tipo) {
            DB::table('tipos_documento')->updateOrInsert(
                [
                    'codigo' => $tipo['codigo'],
                ],
                [
                    'nombre' => $tipo['nombre'],
                    'ambito' => $tipo['ambito'],
                    'descripcion' => $tipo['descripcion'],
                    'requiere_numero' => $tipo['requiere_numero'],
                    'activo' => true,
                    'updated_at' => now(),
                ]
            );
        }
    }
}