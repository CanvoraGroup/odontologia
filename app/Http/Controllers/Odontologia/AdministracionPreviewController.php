<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdministracionPreviewController extends Controller
{
    public function users(Request $request)
    {
        $tab = $request->get('tab', 'personal');
        abort_unless(in_array($tab, ['personal', 'pacientes'], true), 404);

        $personal = [
            ['name' => 'Ana Torres', 'email' => 'ana.torres@demo.test', 'profiles' => ['Administrador'], 'status' => 'ACTIVO', 'link' => 'Sin vínculo'],
            ['name' => 'Luis Mendoza', 'email' => 'luis.mendoza@demo.test', 'profiles' => ['Odontólogo', 'Supervisor'], 'status' => 'ACTIVO', 'link' => 'Odontólogo · Dra. Mendoza'],
            ['name' => 'Carla Ruiz', 'email' => 'carla.ruiz@demo.test', 'profiles' => ['Recepción'], 'status' => 'INACTIVO', 'link' => 'Sin vínculo'],
        ];
        $patients = [
            ['name' => 'María López García', 'email' => 'maria.lopez@demo.test', 'patient' => 'Paciente de ejemplo · HC-000125', 'status' => 'ACTIVO', 'access' => 'Habilitado'],
            ['name' => 'Carlos Ramírez Soto', 'email' => 'carlos.ramirez@demo.test', 'patient' => 'Paciente de ejemplo · HC-000126', 'status' => 'PENDIENTE', 'access' => 'Pendiente de verificación'],
            ['name' => 'Elena Vargas Díaz', 'email' => 'elena.vargas@demo.test', 'patient' => 'Sin paciente vinculado', 'status' => 'INACTIVO', 'access' => 'Deshabilitado'],
        ];

        return view('odontologia.administracion.preview-users', compact('tab', 'personal', 'patients'));
    }

    public function userForm(Request $request)
    {
        $tab = $request->get('tab', 'personal');
        abort_unless(in_array($tab, ['personal', 'pacientes'], true), 404);
        $profiles = ['Administrador', 'Recepción', 'Caja', 'Odontólogo', 'Supervisor'];
        $odontologos = ['Dra. Ana Torres', 'Dr. Luis Mendoza'];
        $pacientes = ['María López García', 'Carlos Ramírez Soto'];

        return view('odontologia.administracion.preview-user-form', compact('tab', 'profiles', 'odontologos', 'pacientes'));
    }

    public function roles()
    {
        $modules = [
            ['name' => 'Pacientes', 'permissions' => ['Ver', 'Crear', 'Editar', 'Consultar historias clínicas']],
            ['name' => 'Citas', 'permissions' => ['Ver', 'Crear', 'Editar']],
            ['name' => 'Atención clínica', 'permissions' => ['Ver', 'Iniciar atención', 'Finalizar atención']],
            ['name' => 'Caja y pagos', 'permissions' => ['Ver', 'Crear', 'Editar', 'Exonerar', 'Anular pago']],
            ['name' => 'Odontogramas', 'permissions' => ['Ver', 'Crear', 'Editar']],
            ['name' => 'Usuarios y perfiles', 'permissions' => ['Ver', 'Crear', 'Editar']],
        ];
        $profiles = ['Administrador', 'Recepción', 'Caja', 'Odontólogo', 'Supervisor'];

        return view('odontologia.administracion.preview-roles', compact('modules', 'profiles'));
    }

    public function account()
    {
        return view('odontologia.administracion.preview-account');
    }

    public function portal(string $section = 'inicio')
    {
        $allowed = ['inicio', 'citas', 'atenciones', 'tratamiento', 'indicaciones', 'documentos', 'pagos'];
        abort_unless(in_array($section, $allowed, true), 404);

        $sections = [
            'inicio' => ['title' => 'Inicio', 'icon' => 'bi-house', 'intro' => 'Resumen de tu atención odontológica.'],
            'citas' => ['title' => 'Mis citas', 'icon' => 'bi-calendar2-week', 'intro' => 'Consulta tus próximas citas y su estado.'],
            'atenciones' => ['title' => 'Mis atenciones', 'icon' => 'bi-clipboard2-pulse', 'intro' => 'Resúmenes clínicos preparados para ti.'],
            'tratamiento' => ['title' => 'Mi tratamiento', 'icon' => 'bi-list-check', 'intro' => 'Revisa el avance general de tu tratamiento.'],
            'indicaciones' => ['title' => 'Mis indicaciones', 'icon' => 'bi-chat-square-text', 'intro' => 'Cuidados y recomendaciones publicados por tu equipo.'],
            'documentos' => ['title' => 'Mis documentos', 'icon' => 'bi-folder2-open', 'intro' => 'Documentos disponibles para consulta.'],
            'pagos' => ['title' => 'Mis pagos', 'icon' => 'bi-credit-card', 'intro' => 'Consulta pagos y saldo pendiente.'],
        ];

        return view('portal.preview', [
            'section' => $section,
            'current' => $sections[$section],
            'sections' => $sections,
        ]);
    }
}
