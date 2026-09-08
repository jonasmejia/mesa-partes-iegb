<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
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
        | PERMISOS DEL SISTEMA
        |--------------------------------------------------------------------------
        */

        $permisos = [

            /*
            |--------------------------------------------------------------------------
            | DASHBOARD
            |--------------------------------------------------------------------------
            */
            'dashboard.ver',

            /*
            |--------------------------------------------------------------------------
            | USUARIOS
            |--------------------------------------------------------------------------
            */
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'usuarios.activar',
            'usuarios.asignar-rol',
            'usuarios.asignar-cargo',

            /*
            |--------------------------------------------------------------------------
            | ROLES Y PERMISOS
            |--------------------------------------------------------------------------
            */
            'roles.ver',
            'roles.crear',
            'roles.editar',
            'roles.eliminar',
            'roles.asignar-permisos',

            /*
            |--------------------------------------------------------------------------
            | ÁREAS
            |--------------------------------------------------------------------------
            */
            'areas.ver',
            'areas.crear',
            'areas.editar',
            'areas.eliminar',
            'areas.activar',

            /*
            |--------------------------------------------------------------------------
            | CARGOS
            |--------------------------------------------------------------------------
            */
            'cargos.ver',
            'cargos.crear',
            'cargos.editar',
            'cargos.eliminar',
            'cargos.activar',

            /*
            |--------------------------------------------------------------------------
            | ESTADOS
            |--------------------------------------------------------------------------
            */
            'estados.ver',
            'estados.crear',
            'estados.editar',
            'estados.eliminar',

            /*
            |--------------------------------------------------------------------------
            | PRIORIDADES
            |--------------------------------------------------------------------------
            */
            'prioridades.ver',
            'prioridades.crear',
            'prioridades.editar',
            'prioridades.eliminar',

            /*
            |--------------------------------------------------------------------------
            | TIPOS DE DOCUMENTO
            |--------------------------------------------------------------------------
            */
            'tipos-documento.ver',
            'tipos-documento.crear',
            'tipos-documento.editar',
            'tipos-documento.eliminar',

            /*
            |--------------------------------------------------------------------------
            | TIPOS DE ACTUACIÓN
            |--------------------------------------------------------------------------
            */
            'tipos-actuacion.ver',
            'tipos-actuacion.crear',
            'tipos-actuacion.editar',
            'tipos-actuacion.eliminar',

            /*
            |--------------------------------------------------------------------------
            | REMITENTES EXTERNOS
            |--------------------------------------------------------------------------
            */
            'remitentes.ver',
            'remitentes.crear',
            'remitentes.editar',

            /*
            |--------------------------------------------------------------------------
            | DOCUMENTOS
            |--------------------------------------------------------------------------
            */
            'documentos.ver',
            'documentos.ver-todos',
            'documentos.ver-area',
            'documentos.crear',
            'documentos.editar',
            'documentos.eliminar',
            'documentos.anular',
            'documentos.archivar',

            /*
            |--------------------------------------------------------------------------
            | DOCUMENTOS EXTERNOS
            |--------------------------------------------------------------------------
            */
            'documentos-externos.ver',
            'documentos-externos.crear',
            'documentos-externos.editar',
            'documentos-externos.admitir',
            'documentos-externos.observar',
            'documentos-externos.rechazar',

            /*
            |--------------------------------------------------------------------------
            | DOCUMENTOS INTERNOS
            |--------------------------------------------------------------------------
            */
            'documentos-internos.ver',
            'documentos-internos.crear',
            'documentos-internos.editar',

            /*
            |--------------------------------------------------------------------------
            | ARCHIVOS DE DOCUMENTOS
            |--------------------------------------------------------------------------
            */
            'documento-archivos.ver',
            'documento-archivos.subir',
            'documento-archivos.descargar',
            'documento-archivos.eliminar',

            /*
            |--------------------------------------------------------------------------
            | EXPEDIENTES
            |--------------------------------------------------------------------------
            */
            'expedientes.ver',
            'expedientes.crear',
            'expedientes.editar',
            'expedientes.cerrar',
            'expedientes.archivar',

            /*
            |--------------------------------------------------------------------------
            | DERIVACIONES
            |--------------------------------------------------------------------------
            */
            'derivaciones.ver',
            'derivaciones.crear',
            'derivaciones.recibir',
            'derivaciones.rechazar',
            'derivaciones.cancelar',

            /*
            |--------------------------------------------------------------------------
            | ARCHIVOS DE DERIVACIÓN
            |--------------------------------------------------------------------------
            */
            'derivacion-archivos.ver',
            'derivacion-archivos.subir',
            'derivacion-archivos.descargar',
            'derivacion-archivos.eliminar',

            /*
            |--------------------------------------------------------------------------
            | ACTUACIONES
            |--------------------------------------------------------------------------
            */
            'actuaciones.ver',
            'actuaciones.crear',
            'actuaciones.editar',
            'actuaciones.eliminar',
            'actuaciones.finalizar',

            /*
            |--------------------------------------------------------------------------
            | ARCHIVOS DE ACTUACIÓN
            |--------------------------------------------------------------------------
            */
            'actuacion-archivos.ver',
            'actuacion-archivos.subir',
            'actuacion-archivos.descargar',
            'actuacion-archivos.eliminar',

            /*
            |--------------------------------------------------------------------------
            | MOVIMIENTOS
            |--------------------------------------------------------------------------
            */
            'movimientos.ver',

            /*
            |--------------------------------------------------------------------------
            | RECEPCIONES
            |--------------------------------------------------------------------------
            */
            'recepciones.ver',
            'recepciones.registrar',

            /*
            |--------------------------------------------------------------------------
            | OBSERVACIONES
            |--------------------------------------------------------------------------
            */
            'observaciones.ver',
            'observaciones.crear',
            'observaciones.editar',
            'observaciones.levantar',

            /*
            |--------------------------------------------------------------------------
            | SUBSANACIONES
            |--------------------------------------------------------------------------
            */
            'subsanaciones.ver',
            'subsanaciones.registrar',
            'subsanaciones.evaluar',
            'subsanaciones.aceptar',
            'subsanaciones.rechazar',

            /*
            |--------------------------------------------------------------------------
            | NOTIFICACIONES
            |--------------------------------------------------------------------------
            */
            'notificaciones.ver',
            'notificaciones.crear',
            'notificaciones.enviar',

            /*
            |--------------------------------------------------------------------------
            | SEGUIMIENTO
            |--------------------------------------------------------------------------
            */
            'seguimiento.ver',

            /*
            |--------------------------------------------------------------------------
            | REPORTES
            |--------------------------------------------------------------------------
            */
            'reportes.ver',
            'reportes.exportar',

            /*
            |--------------------------------------------------------------------------
            | AUDITORÍA
            |--------------------------------------------------------------------------
            */
            'auditorias.ver',

            /*
            |--------------------------------------------------------------------------
            | CORRELATIVOS
            |--------------------------------------------------------------------------
            */
            'correlativos.ver',
            'correlativos.configurar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | VOLVER A LIMPIAR CACHE
        |--------------------------------------------------------------------------
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}