<?php

namespace Database\Seeders;

use App\Models\Odontologia\Permiso;
use App\Models\Odontologia\Rol;
use Illuminate\Database\Seeder;

class PermissionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'usuarios' => ['ver', 'crear', 'editar', 'activar'],
            'perfiles' => ['ver', 'editar'],
            'pacientes' => ['ver', 'crear', 'editar', 'consultar_historias', 'desactivar', 'reactivar'],
            'odontologos' => ['ver', 'crear', 'editar', 'desactivar', 'reactivar', 'documentos_ver', 'documentos_gestionar', 'documentos_revisar', 'horarios_ver', 'horarios_gestionar', 'bloqueos_gestionar'],
            'citas' => ['ver', 'crear', 'editar'],
            'atenciones' => ['ver', 'editar', 'iniciar', 'finalizar'],
            'pagos' => ['ver', 'crear', 'editar', 'exonerar', 'anular'],
            'odontogramas' => ['ver', 'crear', 'editar'],
            'catalogos' => ['ver', 'crear', 'editar', 'activar'],
            'planes' => ['ver', 'crear', 'editar', 'enviar', 'responder', 'precios', 'descuentos', 'imprimir', 'sesiones_ver', 'sesiones_crear', 'sesiones_editar', 'sesiones_finalizar', 'sesiones_anular', 'finalizar'],
            'configuracion' => ['ver', 'editar'],
            'recetas' => ['ver', 'crear', 'editar', 'imprimir', 'anular'],
            'reportes' => ['cobros_ver', 'cobros_exportar', 'cobros_imprimir', 'saldos_ver', 'saldos_exportar', 'saldos_imprimir', 'citas_ver', 'citas_exportar', 'citas_imprimir', 'tratamientos_ver', 'tratamientos_exportar', 'tratamientos_imprimir', 'presupuestos_ver', 'presupuestos_exportar', 'presupuestos_imprimir', 'produccion_ver', 'produccion_exportar', 'produccion_imprimir'],
        ];

        $permissions = [];
        foreach ($catalog as $module => $actions) {
            foreach ($actions as $action) {
                $permissions[$module.'.'.$action] = Permiso::firstOrCreate(
                    ['modulo' => $module, 'accion' => $action],
                    ['descripcion' => ucfirst(str_replace('_', ' ', $action))]
                );
            }
        }

        $roles = [
            'ADMINISTRADOR' => array_values(array_filter(array_keys($permissions), fn ($name) => ! in_array($name, ['recetas.crear', 'recetas.editar', 'recetas.anular'], true))),
            'ODONTOLOGO' => ['odontologos.ver', 'pacientes.ver', 'pacientes.consultar_historias', 'citas.ver', 'atenciones.ver', 'atenciones.editar', 'atenciones.iniciar', 'atenciones.finalizar', 'odontogramas.ver', 'odontogramas.crear', 'odontogramas.editar', 'planes.ver', 'planes.crear', 'planes.editar', 'planes.enviar', 'planes.imprimir', 'planes.sesiones_ver', 'planes.sesiones_crear', 'planes.sesiones_editar', 'planes.sesiones_finalizar', 'recetas.ver', 'recetas.crear', 'recetas.editar', 'recetas.imprimir'],
            'RECEPCION' => ['odontologos.ver', 'pacientes.ver', 'pacientes.crear', 'pacientes.editar', 'citas.ver', 'citas.crear', 'citas.editar'],
            'CAJA' => ['citas.ver', 'pagos.ver', 'pagos.crear', 'pagos.editar', 'pagos.exonerar', 'pagos.anular'],
            'SUPERVISOR' => ['odontologos.ver', 'pacientes.ver', 'pacientes.consultar_historias', 'citas.ver', 'atenciones.ver', 'pagos.ver', 'odontogramas.ver', 'planes.ver', 'planes.imprimir', 'planes.responder', 'planes.sesiones_ver', 'planes.sesiones_anular', 'planes.finalizar', 'recetas.ver', 'recetas.imprimir'],
        ];

        foreach ($roles as $roleName => $permissionNames) {
            $role = Rol::firstOrCreate(
                ['nombre' => $roleName],
                ['descripcion' => 'Perfil inicial de '.$roleName, 'estado' => 'ACTIVO']
            );

            $role->permisos()->syncWithoutDetaching(
                collect($permissionNames)->map(fn ($name) => $permissions[$name]->id_permiso)->all()
            );
        }
    }
}
