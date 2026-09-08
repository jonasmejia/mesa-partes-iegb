<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ÁREAS PRINCIPALES
        |--------------------------------------------------------------------------
        |
        | Primero registramos las áreas que no dependen de otra área.
        | Utilizamos updateOrInsert para que el seeder pueda ejecutarse
        | nuevamente sin duplicar registros.
        |
        */

        DB::table('areas')->updateOrInsert(
            ['codigo' => 'DIR-GRAL'],
            [
                'nombre' => 'Dirección General',
                'descripcion' => 'Órgano responsable de la dirección y gestión general de la institución.',
                'sigla' => 'DG',
                'area_padre_id' => null,
                'recibe_documentos' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('areas')->updateOrInsert(
            ['codigo' => 'UA'],
            [
                'nombre' => 'Unidad Académica',
                'descripcion' => 'Unidad responsable de la gestión y coordinación de las actividades académicas.',
                'sigla' => 'UA',
                'area_padre_id' => null,
                'recibe_documentos' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('areas')->updateOrInsert(
            ['codigo' => 'SA'],
            [
                'nombre' => 'Secretaría Académica',
                'descripcion' => 'Área responsable de los procesos y registros académicos de la institución.',
                'sigla' => 'SA',
                'area_padre_id' => null,
                'recibe_documentos' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('areas')->updateOrInsert(
            ['codigo' => 'UA-ADM'],
            [
                'nombre' => 'Unidad Administrativa',
                'descripcion' => 'Unidad responsable de la gestión administrativa y de los recursos institucionales.',
                'sigla' => 'UADM',
                'area_padre_id' => null,
                'recibe_documentos' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('areas')->updateOrInsert(
            ['codigo' => 'CAL'],
            [
                'nombre' => 'Área de Calidad',
                'descripcion' => 'Área responsable de los procesos de gestión y aseguramiento de la calidad institucional.',
                'sigla' => 'CAL',
                'area_padre_id' => null,
                'recibe_documentos' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | OBTENER ID DE LA UNIDAD ACADÉMICA
        |--------------------------------------------------------------------------
        */

        $unidadAcademicaId = DB::table('areas')
            ->where('codigo', 'UA')
            ->value('id');

        /*
        |--------------------------------------------------------------------------
        | PROGRAMAS DE ESTUDIOS
        |--------------------------------------------------------------------------
        |
        | Los programas dependen de la Unidad Académica.
        |
        */

        $programas = [
            [
                'codigo' => 'PE-ARC',
                'nombre' => 'Administración de Redes y Comunicaciones',
                'sigla' => 'ARC',
            ],
            [
                'codigo' => 'PE-CON',
                'nombre' => 'Contabilidad',
                'sigla' => 'CON',
            ],
            [
                'codigo' => 'PE-ENF',
                'nombre' => 'Enfermería Técnica',
                'sigla' => 'ENF',
            ],
            [
                'codigo' => 'PE-PAG',
                'nombre' => 'Producción Agropecuaria',
                'sigla' => 'PAG',
            ],
            [
                'codigo' => 'PE-ELI',
                'nombre' => 'Electricidad Industrial',
                'sigla' => 'ELI',
            ],
            [
                'codigo' => 'PE-MAU',
                'nombre' => 'Mecánica Automotriz',
                'sigla' => 'MAU',
            ],
            [
                'codigo' => 'PE-GOT',
                'nombre' => 'Guía Oficial de Turismo',
                'sigla' => 'GOT',
            ],
            [
                'codigo' => 'PE-FAR',
                'nombre' => 'Farmacia Técnica',
                'sigla' => 'FAR',
            ],
            [
                'codigo' => 'PE-GNT',
                'nombre' => 'Gastronomía',
                'sigla' => 'GNT',
            ],
        ];

        foreach ($programas as $programa) {
            DB::table('areas')->updateOrInsert(
                ['codigo' => $programa['codigo']],
                [
                    'nombre' => $programa['nombre'],
                    'descripcion' => 'Programa de Estudios de ' . $programa['nombre'] . '.',
                    'sigla' => $programa['sigla'],
                    'area_padre_id' => $unidadAcademicaId,
                    'recibe_documentos' => true,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ÁREAS DEPENDIENTES DE LA UNIDAD ADMINISTRATIVA
        |--------------------------------------------------------------------------
        */

        $unidadAdministrativaId = DB::table('areas')
            ->where('codigo', 'UA-ADM')
            ->value('id');

        $areasAdministrativas = [
            [
                'codigo' => 'TES',
                'nombre' => 'Tesorería',
                'sigla' => 'TES',
            ],
            [
                'codigo' => 'ABAST',
                'nombre' => 'Abastecimiento',
                'sigla' => 'ABAST',
            ],
            [
                'codigo' => 'RRHH',
                'nombre' => 'Recursos Humanos',
                'sigla' => 'RRHH',
            ],
        ];

        foreach ($areasAdministrativas as $area) {
            DB::table('areas')->updateOrInsert(
                ['codigo' => $area['codigo']],
                [
                    'nombre' => $area['nombre'],
                    'descripcion' => 'Área de ' . $area['nombre'] . '.',
                    'sigla' => $area['sigla'],
                    'area_padre_id' => $unidadAdministrativaId,
                    'recibe_documentos' => true,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    
    }
}
