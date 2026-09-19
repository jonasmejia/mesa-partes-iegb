<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosInstitucionalesSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'name' => 'Carlos Carrasco Giraldo',
                'email' => 'carlos.carrasco@iegb.edu.pe',
            ],
            [
                'name' => 'Lic. Silvia Prueba',
                'email' => 'silvia.prueba@iegb.edu.pe',
            ],
            [
                'name' => 'Victor Guardia Tamara',
                'email' => 'victor.guardia@iegb.edu.pe',
            ],
            [
                'name' => 'Lic. Prueba Secretaria',
                'email' => 'secretaria.prueba@iegb.edu.pe',
            ],
            [
                'name' => 'Maria Tarazona Jimenez',
                'email' => 'maria.tarazona@iegb.edu.pe',
            ],
            [
                'name' => 'Edwin Sanchez Rios',
                'email' => 'edwin.sanchez@iegb.edu.pe',
            ],
            [
                'name' => 'Jonathan Mejía Palacios',
                'email' => 'jonathan.mejia@iegb.edu.pe',
            ],
            [
                'name' => 'Juan Pablo García Valenzuela',
                'email' => 'juan.garcia@iegb.edu.pe',
            ],
            [
                'name' => 'Angelica Armas Huaman',
                'email' => 'angelica.armas@iegb.edu.pe',
            ],
            [
                'name' => 'Enrique Medina Regalado',
                'email' => 'enrique.medina@iegb.edu.pe',
            ],
            [
                'name' => 'Richar Trejo Maguiña',
                'email' => 'richar.trejo@iegb.edu.pe',
            ],
            [
                'name' => 'Rafael Trejo Maguima',
                'email' => 'rafael.trejo@iegb.edu.pe',
            ],
            [
                'name' => 'Pablo Tamará Bernabe',
                'email' => 'pablo.tamara@iegb.edu.pe',
            ],
            [
                'name' => 'Alex Mallqui',
                'email' => 'alex.mallqui@iegb.edu.pe',
            ],
        ];

        foreach ($usuarios as $datos) {
            User::updateOrCreate(
                [
                    'email' => $datos['email'],
                ],
                [
                    'name' => $datos['name'],
                    'password' => Hash::make('Password123*'),
                    'activo' => true,
                ]
            );
        }
    }
}