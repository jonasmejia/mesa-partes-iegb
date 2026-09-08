<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | LIMPIAR CACHE DE SPATIE
        |--------------------------------------------------------------------------
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | USUARIO SUPER ADMINISTRADOR
        |--------------------------------------------------------------------------
        */

        $usuario = User::updateOrCreate(
            [
                'email' => 'admin@iegb.edu.pe',
            ],
            [
                'name' => 'Administrador del Sistema',
                'email_verified_at' => now(),
                'password' => Hash::make('Admin123*'),
                'dni' => '70515941',
                'telefono' => '923905296',
                'activo' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | ASIGNAR ROL SUPER-ADMIN
        |--------------------------------------------------------------------------
        */

        $rol = Role::where('name', 'super-admin')
            ->where('guard_name', 'web')
            ->firstOrFail();

        $usuario->syncRoles([$rol]);

        /*
        |--------------------------------------------------------------------------
        | LIMPIAR CACHE NUEVAMENTE
        |--------------------------------------------------------------------------
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}