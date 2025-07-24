<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        if (auth()->check() && auth()->user()->must_change_password) {
            // Permitimos acceso a las rutas de cambio de contraseña y logout
            if (!$request->is('change-password*') && !$request->is('logout')) {
                return redirect()->route('password.change');
            }
        }

        return $response;
    }
}
