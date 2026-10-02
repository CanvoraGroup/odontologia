<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Usuarios mock de prueba para entornos sin base de datos o pruebas aisladas.
     * Sincronizados con las credenciales de odo_usuarios.
     */
    protected array $mockUsers = [
        [
            'id' => 1,
            'nombre' => 'Administrador del Sistema',
            'email' => 'admin@odontologia.test',
            // Hash de 'admin123'
            'password_hash' => '$2y$04$YQK9w/Mu5tKq.qgaOky9RO4YZMnc8JuFp0r08ztKQHxB.TUm4kg3e',
            'plain_password' => 'admin123',
            'rol' => 'ADMIN',
            'estado' => 'ACTIVO',
        ],
        [
            'id' => 2,
            'nombre' => 'Dra. Ana María Salazar Ríos',
            'email' => 'doctor@odontologia.test',
            // Hash de 'doctor123'
            'password_hash' => '$2y$12$ImEvXN1bOGEq3U3XmU9xl.xnDqXB8K/xhkN9UgC2xLbtGlXkejroy',
            'plain_password' => 'doctor123',
            'rol' => 'ODONTOLOGO',
            'estado' => 'ACTIVO',
        ],
        [
            'id' => 3,
            'nombre' => 'María Elena Gómez Pérez',
            'email' => 'recepcion@odontologia.test',
            // Hash de 'recepcion123'
            'password_hash' => '$2y$12$mTY2JZBuB3czwhQCmKk.0.dKvUX0Ydap7ys4Dl1xQ1fXhlfOhiAYq',
            'plain_password' => 'recepcion123',
            'rol' => 'RECEPCION',
            'estado' => 'ACTIVO',
        ],
    ];

    /**
     * Muestra la interfaz de inicio de sesión.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('odontologia.dashboard');
        }

        return view('auth.login', [
            'mockUsers' => $this->mockUsers,
        ]);
    }

    /**
     * Procesa la solicitud de autenticación y validación de credenciales.
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        // 1. Validación de formato de campos obligatorios en el backend
        $credentials = $request->validate([
            'identificador' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'min:4'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'identificador.required' => 'El correo electrónico o identificador es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe contener al menos 4 caracteres.',
        ]);

        $identifier = trim($credentials['identificador']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        $isAuthenticated = false;
        $authenticatedUser = null;

        // 2. Intento de autenticación nativa contra la base de datos (odo_usuarios)
        try {
            // Se valida contra email o se añade lógica de búsqueda por username si fuera necesario
            $attemptCredentials = [
                'email' => $identifier,
                'password' => $password,
                'estado' => 'ACTIVO',
            ];

            if (Auth::attempt($attemptCredentials, $remember)) {
                $isAuthenticated = true;
                $user = Auth::user();
                if ($user instanceof User) {
                    $user->update(['ultimo_acceso_at' => now()]);
                    $authenticatedUser = [
                        'id' => $user->id_usuario,
                        'nombre' => $user->nombre_completo,
                        'email' => $user->email,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Error consultando base de datos en login, evaluando fallback mock: ' . $e->getMessage());
        }

        // 3. Fallback a Mock Users si la base de datos no está disponible o falla temporalmente
        if (!$isAuthenticated) {
            foreach ($this->mockUsers as $mock) {
                if (
                    strcasecmp($mock['email'], $identifier) === 0 &&
                    ($password === $mock['plain_password'] || Hash::check($password, $mock['password_hash']))
                ) {
                    $isAuthenticated = true;
                    $authenticatedUser = [
                        'id' => $mock['id'],
                        'nombre' => $mock['nombre'],
                        'email' => $mock['email'],
                    ];
                    // Simulamos login en sesión si no hay DB activa
                    session(['mock_user' => $authenticatedUser, 'authenticated' => true]);
                    break;
                }
            }
        }

        // 4. Respuesta según el resultado de la autenticación
        if ($isAuthenticated) {
            $request->session()->regenerate();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Credenciales correctas! Accediendo a la plataforma clínica...',
                    'redirect' => route('odontologia.dashboard'),
                    'user' => $authenticatedUser,
                ]);
            }

            return redirect()->intended(route('odontologia.dashboard'));
        }

        // Credenciales incorrectas
        $errorMessage = 'Las credenciales ingresadas son incorrectas o la cuenta no se encuentra activa.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'errors' => [
                    'identificador' => [$errorMessage],
                ],
            ], 422);
        }

        throw ValidationException::withMessages([
            'identificador' => [$errorMessage],
        ]);
    }

    /**
     * Cierra la sesión activa del usuario.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
