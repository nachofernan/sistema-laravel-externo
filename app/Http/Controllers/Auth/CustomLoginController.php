<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ProvidersRouteServiceProvider;
use Illuminate\Validation\ValidationException;

class CustomLoginController extends Controller
{
    /**
     * Mostrar el formulario de login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Manejar el intento de login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Intentar autenticar al usuario
        if (!Auth::attempt([
            'username' => $request->username,
            'password' => $request->password
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }
        
        $request->session()->regenerate();

        return redirect()->intended(
            config('fortify.home')
        );
    }

    /**
     * Manejar el logout
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Validar la sesión actual
     */
    public function validateSession(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['valid' => false], 401);
        }

        return response()->json(['valid' => true]);
    }
}