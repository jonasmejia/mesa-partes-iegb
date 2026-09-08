<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserCargoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | OBTENER USUARIO ADMINISTRADOR
        |--------------------------------------------------------------------------
        */

        $usuario = User::where('email', 'admin@iegb.edu.pe')->first();

        if (!$usuario) {
            throw new RuntimeException(
                'No se encontró el usuario admin@iegb.edu.pe. '
                . 'Ejecute primero UserSeeder.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | OBTENER CARGO
        |--------------------------------------------------------------------------
        |
        | Este cargo debe existir previamente en CargoSeeder.
        |
        */

        $cargoId = DB::table('cargos')
            ->where('nombre', 'Soporte TIC')
            ->value('id');

        if (!$cargoId) {
            throw new RuntimeException(
                'No se encontró el cargo "Soporte TIC". '
                . 'Ejecute primero CargoSeeder.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | OBTENER ÁREA
        |--------------------------------------------------------------------------
        |
        | DIR-GRAL corresponde a Dirección General según AreaSeeder.
        |
        */

        $areaId = DB::table('areas')
            ->where('codigo', 'DIR-GRAL')
            ->value('id');

        if (!$areaId) {
            throw new RuntimeException(
                'No se encontró el área con código DIR-GRAL. '
                . 'Ejecute primero AreaSeeder.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ASIGNAR CARGO Y ÁREA AL USUARIO
        |--------------------------------------------------------------------------
        */

        DB::table('user_cargos')->updateOrInsert(
            [
                'user_id' => $usuario->id,
                'cargo_id' => $cargoId,
                'area_id' => $areaId,
            ],
            [
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin' => null,
                'es_responsable' => false,
                'activo' => true,
                'updated_at' => now(),
            ]
        );
    }
}