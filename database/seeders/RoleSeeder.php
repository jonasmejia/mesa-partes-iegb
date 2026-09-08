<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | LIMPIAR CACHE
        |--------------------------------------------------------------------------
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMINISTRADOR
        |--------------------------------------------------------------------------
        |
        | Tiene absolutamente todos los permisos registrados.
        |
        */

        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions(
            Permission::where('guard_name', 'web')->get()
        );

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRADOR
        |--------------------------------------------------------------------------
        */

        $administrador = Role::firstOrCreate([
            'name' => 'administrador',
            'guard_name' => 'web',
        ]);

        $administrador->syncPermissions([
            'dashboard.ver',

            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.activar',
            'usuarios.asignar-rol',
            'usuarios.asignar-cargo',

            'roles.ver',

            'areas.ver',
            'areas.crear',
            'areas.editar',
            'areas.activar',

            'cargos.ver',
            'cargos.crear',
            'cargos.editar',
            'cargos.activar',

            'estados.ver',
            'prioridades.ver',
            'tipos-documento.ver',
            'tipos-actuacion.ver',

            'documentos.ver',
            'documentos.ver-todos',
            'documentos.crear',
            'documentos.editar',
            'documentos.anular',
            'documentos.archivar',

            'documentos-externos.ver',
            'documentos-externos.crear',
            'documentos-externos.editar',
            'documentos-externos.admitir',
            'documentos-externos.observar',
            'documentos-externos.rechazar',

            'documentos-internos.ver',
            'documentos-internos.crear',
            'documentos-internos.editar',

            'documento-archivos.ver',
            'documento-archivos.subir',
            'documento-archivos.descargar',

            'expedientes.ver',
            'expedientes.crear',
            'expedientes.editar',
            'expedientes.cerrar',
            'expedientes.archivar',

            'derivaciones.ver',
            'derivaciones.crear',
            'derivaciones.recibir',
            'derivaciones.rechazar',
            'derivaciones.cancelar',

            'derivacion-archivos.ver',
            'derivacion-archivos.subir',
            'derivacion-archivos.descargar',

            'actuaciones.ver',
            'actuaciones.crear',
            'actuaciones.editar',
            'actuaciones.finalizar',

            'actuacion-archivos.ver',
            'actuacion-archivos.subir',
            'actuacion-archivos.descargar',

            'movimientos.ver',

            'recepciones.ver',
            'recepciones.registrar',

            'observaciones.ver',
            'observaciones.crear',
            'observaciones.editar',
            'observaciones.levantar',

            'subsanaciones.ver',
            'subsanaciones.registrar',
            'subsanaciones.evaluar',
            'subsanaciones.aceptar',
            'subsanaciones.rechazar',

            'notificaciones.ver',
            'notificaciones.crear',
            'notificaciones.enviar',

            'seguimiento.ver',

            'reportes.ver',
            'reportes.exportar',

            'auditorias.ver',

            'correlativos.ver',
            'correlativos.configurar',
        ]);

        /*
        |--------------------------------------------------------------------------
        | MESA DE PARTES
        |--------------------------------------------------------------------------
        */

        $mesaPartes = Role::firstOrCreate([
            'name' => 'mesa-partes',
            'guard_name' => 'web',
        ]);

        $mesaPartes->syncPermissions([
            'dashboard.ver',

            'remitentes.ver',
            'remitentes.crear',
            'remitentes.editar',

            'documentos.ver',
            'documentos.crear',
            'documentos.editar',

            'documentos-externos.ver',
            'documentos-externos.crear',
            'documentos-externos.editar',
            'documentos-externos.admitir',
            'documentos-externos.observar',
            'documentos-externos.rechazar',

            'documento-archivos.ver',
            'documento-archivos.subir',
            'documento-archivos.descargar',

            'expedientes.ver',
            'expedientes.crear',

            'derivaciones.ver',
            'derivaciones.crear',

            'derivacion-archivos.ver',
            'derivacion-archivos.subir',
            'derivacion-archivos.descargar',

            'movimientos.ver',

            'recepciones.ver',

            'observaciones.ver',
            'observaciones.crear',

            'subsanaciones.ver',

            'notificaciones.ver',

            'seguimiento.ver',

            'reportes.ver',
        ]);

        /*
        |--------------------------------------------------------------------------
        | RESPONSABLE DE ÁREA
        |--------------------------------------------------------------------------
        */

        $responsableArea = Role::firstOrCreate([
            'name' => 'responsable-area',
            'guard_name' => 'web',
        ]);

        $responsableArea->syncPermissions([
            'dashboard.ver',

            'documentos.ver',
            'documentos.ver-area',

            'documentos-internos.ver',
            'documentos-internos.crear',
            'documentos-internos.editar',

            'documento-archivos.ver',
            'documento-archivos.subir',
            'documento-archivos.descargar',

            'expedientes.ver',

            'derivaciones.ver',
            'derivaciones.crear',
            'derivaciones.recibir',
            'derivaciones.rechazar',

            'derivacion-archivos.ver',
            'derivacion-archivos.subir',
            'derivacion-archivos.descargar',

            'actuaciones.ver',
            'actuaciones.crear',
            'actuaciones.editar',
            'actuaciones.finalizar',

            'actuacion-archivos.ver',
            'actuacion-archivos.subir',
            'actuacion-archivos.descargar',

            'movimientos.ver',

            'recepciones.ver',
            'recepciones.registrar',

            'observaciones.ver',
            'observaciones.crear',
            'observaciones.editar',
            'observaciones.levantar',

            'subsanaciones.ver',
            'subsanaciones.evaluar',
            'subsanaciones.aceptar',
            'subsanaciones.rechazar',

            'notificaciones.ver',
            'notificaciones.crear',
            'notificaciones.enviar',

            'seguimiento.ver',

            'reportes.ver',
        ]);

        /*
        |--------------------------------------------------------------------------
        | USUARIO INTERNO
        |--------------------------------------------------------------------------
        */

        $usuarioInterno = Role::firstOrCreate([
            'name' => 'usuario-interno',
            'guard_name' => 'web',
        ]);

        $usuarioInterno->syncPermissions([
            'dashboard.ver',

            'documentos.ver',
            'documentos.ver-area',

            'documentos-internos.ver',
            'documentos-internos.crear',

            'documento-archivos.ver',
            'documento-archivos.subir',
            'documento-archivos.descargar',

            'derivaciones.ver',
            'derivaciones.crear',
            'derivaciones.recibir',

            'derivacion-archivos.ver',
            'derivacion-archivos.subir',
            'derivacion-archivos.descargar',

            'actuaciones.ver',
            'actuaciones.crear',

            'actuacion-archivos.ver',
            'actuacion-archivos.subir',
            'actuacion-archivos.descargar',

            'movimientos.ver',

            'recepciones.ver',
            'recepciones.registrar',

            'observaciones.ver',

            'seguimiento.ver',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CONSULTA / AUDITOR
        |--------------------------------------------------------------------------
        */

        $auditor = Role::firstOrCreate([
            'name' => 'auditor',
            'guard_name' => 'web',
        ]);

        $auditor->syncPermissions([
            'dashboard.ver',

            'documentos.ver',
            'documentos.ver-todos',

            'documentos-externos.ver',
            'documentos-internos.ver',

            'documento-archivos.ver',
            'documento-archivos.descargar',

            'expedientes.ver',

            'derivaciones.ver',
            'derivacion-archivos.ver',
            'derivacion-archivos.descargar',

            'actuaciones.ver',
            'actuacion-archivos.ver',
            'actuacion-archivos.descargar',

            'movimientos.ver',
            'recepciones.ver',
            'observaciones.ver',
            'subsanaciones.ver',
            'notificaciones.ver',

            'seguimiento.ver',

            'reportes.ver',
            'reportes.exportar',

            'auditorias.ver',
        ]);

        /*
        |--------------------------------------------------------------------------
        | LIMPIAR CACHE NUEVAMENTE
        |--------------------------------------------------------------------------
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}