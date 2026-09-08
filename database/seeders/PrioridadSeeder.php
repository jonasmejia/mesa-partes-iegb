<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrioridadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prioridades = [
            [
                'codigo' => 'NORMAL',
                'nombre' => 'Normal',
                'nivel' => 1,
                'horas_objetivo' => null,
            ],
            [
                'codigo' => 'ALTA',
                'nombre' => 'Alta',
                'nivel' => 2,
                'horas_objetivo' => 48,
            ],
            [
                'codigo' => 'URGENTE',
                'nombre' => 'Urgente',
                'nivel' => 3,
                'horas_objetivo' => 24,
            ],
        ];

        foreach ($prioridades as $prioridad) {
            DB::table('prioridades')->updateOrInsert(
                [
                    'codigo' => $prioridad['codigo'],
                ],
                [
                    'nombre' => $prioridad['nombre'],
                    'nivel' => $prioridad['nivel'],
                    'horas_objetivo' => $prioridad['horas_objetivo'],
                    'activo' => true,
                    'updated_at' => now(),
                ]
            );
        }
    }
}