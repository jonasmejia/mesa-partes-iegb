<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\User;
use App\Models\UserCargo;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserCargoInstitucionalSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Configuración de asignaciones
        |--------------------------------------------------------------------------
        */

        $asignaciones = [

            // Dirección General
            [
                'email' => 'carlos.carrasco@iegb.edu.pe',
                'cargo' => 'Director General',
                'area' => 'DIR-GRAL',
                'es_responsable' => true,
            ],
            [
                'email' => 'silvia.prueba@iegb.edu.pe',
                'cargo' => 'Secretaria',
                'area' => 'DIR-GRAL',
                'es_responsable' => false,
            ],

            // Secretaría Académica
            [
                'email' => 'victor.guardia@iegb.edu.pe',
                'cargo' => 'Secretario Académico',
                'area' => 'SA',
                'es_responsable' => true,
            ],
            [
                'email' => 'secretaria.prueba@iegb.edu.pe',
                'cargo' => 'Asistente Administrativo',
                'area' => 'SA',
                'es_responsable' => false,
            ],

            // Unidad Académica
            [
                'email' => 'maria.tarazona@iegb.edu.pe',
                'cargo' => 'Jefe de Unidad Académica',
                'area' => 'UA',
                'es_responsable' => true,
            ],

            // Calidad
            [
                'email' => 'edwin.sanchez@iegb.edu.pe',
                'cargo' => 'Jefe de Calidad',
                'area' => 'CAL',
                'es_responsable' => true,
            ],

            // P.E. Administración de Redes y Comunicaciones
            [
                'email' => 'jonathan.mejia@iegb.edu.pe',
                'cargo' => 'Coordinador de Programa de Estudios',
                'area' => 'PE-ARC',
                'es_responsable' => true,
            ],
            [
                'email' => 'juan.garcia@iegb.edu.pe',
                'cargo' => 'Docente',
                'area' => 'PE-ARC',
                'es_responsable' => false,
            ],
            [
                'email' => 'angelica.armas@iegb.edu.pe',
                'cargo' => 'Docente',
                'area' => 'PE-ARC',
                'es_responsable' => false,
            ],
            [
                'email' => 'enrique.medina@iegb.edu.pe',
                'cargo' => 'Docente',
                'area' => 'PE-ARC',
                'es_responsable' => false,
            ],

            // P.E. Contabilidad
            [
                'email' => 'richar.trejo@iegb.edu.pe',
                'cargo' => 'Coordinador de Programa de Estudios',
                'area' => 'PE-CON',
                'es_responsable' => true,
            ],
            [
                'email' => 'rafael.trejo@iegb.edu.pe',
                'cargo' => 'Docente',
                'area' => 'PE-CON',
                'es_responsable' => false,
            ],
            [
                'email' => 'pablo.tamara@iegb.edu.pe',
                'cargo' => 'Docente',
                'area' => 'PE-CON',
                'es_responsable' => false,
            ],
            [
                'email' => 'alex.mallqui@iegb.edu.pe',
                'cargo' => 'Docente',
                'area' => 'PE-CON',
                'es_responsable' => false,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Registrar asignaciones
        |--------------------------------------------------------------------------
        */

        foreach ($asignaciones as $datos) {

            $usuario = User::query()
                ->where('email', $datos['email'])
                ->first();

            if (! $usuario) {
                throw new RuntimeException(
                    "No existe el usuario {$datos['email']}."
                );
            }

            $area = Area::query()
                ->where('codigo', $datos['area'])
                ->first();

            if (! $area) {
                throw new RuntimeException(
                    "No existe el área {$datos['area']}."
                );
            }

            $cargo = Cargo::query()
                ->where('nombre', $datos['cargo'])
                ->first();

            if (! $cargo) {
                throw new RuntimeException(
                    "No existe el cargo {$datos['cargo']}."
                );
            }

            UserCargo::updateOrCreate(
                [
                    'user_id' => $usuario->id,
                    'cargo_id' => $cargo->id,
                    'area_id' => $area->id,
                ],
                [
                    'fecha_inicio' => '2026-01-01',
                    'fecha_fin' => null,
                    'es_responsable' => $datos['es_responsable'],
                    'activo' => true,
                ]
            );
        }
    }
}