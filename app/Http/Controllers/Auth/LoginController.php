<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['estado'] = 'ACTIVO';

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Las credenciales no son válidas o el usuario está inactivo.']);
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['ultimo_acceso_at' => now()])->save();

        $destination = $request->user()->isPlatformAdministrator()
            ? route('odontologia.plataforma.empresas.index')
            : route('odontologia.atenciones.index');
        return redirect()->intended($destination);
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
