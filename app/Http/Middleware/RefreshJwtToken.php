<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;
use App\Models\EventoUsuario;

class RefreshJwtToken
{
    /**
     * Tiempo (en segundos) antes de la expiración para refrescar el token
     */
    private const REFRESH_MARGIN = 120; // 2 minutos

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $token = session('jwt_token');
            if ($token) {
                try {
                    $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
                    $exp = $decoded->exp ?? 0;
                    $now = time();
                    if ($exp - $now < self::REFRESH_MARGIN) {
                        // Refrescar token
                        $newToken = app(\App\Http\Controllers\AuthController::class)->getNewToken();
                        session(['jwt_token' => $newToken]);
                        Log::info('JWT token refrescado automáticamente para el usuario', ['user_id' => Auth::id(), 'old_exp' => $exp, 'now' => $now]);
                    }
                } catch (\Exception $e) {
                    // Si el token es inválido o expiró, intentar renovarlo
                    try {
                        $newToken = app(\App\Http\Controllers\AuthController::class)->getNewToken();
                        session(['jwt_token' => $newToken]);
                        Log::info('JWT token renovado tras excepción', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
                    } catch (\Exception $ex) {
                        Log::error('Fallo crítico de JWT: Limpiando sesión', [
                            'user_id' => Auth::id(),
                            'error' => $ex->getMessage()
                        ]);

                        $user = Auth::user();
                        Auth::logout();
                        EventoUsuario::registrar('logout', $user, detalle: ['motivo' => 'jwt_invalido']);

                        // ✅ Estas 3 líneas limpian las cookies y la sesión del lado del servidor
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                        
                        return redirect()->route('login')
                            ->with('error', 'Su sesión ha expirado. Ingrese nuevamente.')
                            ->withCookie(cookie()->forget('laravel_session')); // Forzar borrado de cookie
                    }
                }
            } else {
                Log::warning('No hay token en sesión al entrar al middleware', ['user_id' => Auth::id()]);
                // No hay token en sesión, forzar logout
                $user = Auth::user();
                Auth::guard('web')->logout();
                EventoUsuario::registrar('logout', $user, detalle: ['motivo' => 'sin_token_en_sesion']);
                session()->invalidate();
                session()->regenerateToken();
                return redirect()->route('login')->with('error', 'La sesión expiró. Por favor, ingrese nuevamente.');
            }
        }
        return $next($request);
    }
} 