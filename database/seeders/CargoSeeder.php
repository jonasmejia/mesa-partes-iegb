<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CargoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cargos = [
            [
                'nombre' => 'Director General',
                'descripcion' => 'Máxima autoridad responsable de la dirección y gestión institucional.',
            ],
            [
                'nombre' => 'Jefe de Unidad Académica',
                'descripcion' => 'Responsable de la gestión y coordinación de las actividades académicas.',
            ],
            [
                'nombre' => 'Secretario Académico',
                'descripcion' => 'Responsable de los procesos, registros y documentación académica institucional.',
            ],
            [
                'nombre' => 'Jefe de Unidad Administrativa',
                'descripcion' => 'Responsable de la gestión administrativa y de los recursos institucionales.',
            ],
            [
                'nombre' => 'Jefe de Calidad',
                'descripcion' => 'Responsable de los procesos de gestión y aseguramiento de la calidad institucional.',
            ],
            [
                'nombre' => 'Coordinador de Programa de Estudios',
                'descripcion' => 'Responsable de la coordinación académica y administrativa de un programa de estudios.',
            ],
            [
                'nombre' => 'Docente',
                'descripcion' => 'Personal encargado del desarrollo de actividades académicas y formativas.',
            ],
            [
                'nombre' => 'Responsable de Mesa de Partes',
                'descripcion' => 'Responsable de la recepción, registro y distribución inicial de documentos.',
            ],
            [
                'nombre' => 'Secretaria',
                'descripcion' => 'Personal encargado del apoyo administrativo, recepción y gestión de documentación.',
            ],
            [
                'nombre' => 'Asistente Administrativo',
                'descripcion' => 'Personal encargado de brindar apoyo en los procesos administrativos.',
            ],
            [
                'nombre' => 'Responsable de Tesorería',
                'descripcion' => 'Responsable de los procesos correspondientes al área de tesorería.',
            ],
            [
                'nombre' => 'Responsable de Abastecimiento',
                'descripcion' => 'Responsable de los procesos correspondientes al área de abastecimiento.',
            ],
            [
                'nombre' => 'Responsable de Recursos Humanos',
                'descripcion' => 'Responsable de los procesos relacionados con la gestión de recursos humanos.',
            ],
            [
                'nombre' => 'Soporte TIC',
                'descripcion' => 'Responsable del soporte tecnológico, sistemas de información e infraestructura TIC.',
            ],
            [
                'nombre' => 'Personal Administrativo',
                'descripcion' => 'Personal que desarrolla funciones administrativas dentro de la institución.',
            ],
        ];

        foreach ($cargos as $cargo) {
            DB::table('cargos')->updateOrInsert(
                [
                    'nombre' => $cargo['nombre'],
                ],
                [
                    'descripcion' => $cargo['descripcion'],
                    'activo' => true,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
