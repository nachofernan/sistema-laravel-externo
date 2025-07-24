<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TemporaryPasswordMail;
use App\Models\LoginAttempt;
use App\Models\Proveedores\Proveedor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ProgressiveLoginController extends Controller
{
    public function showLoginForm()
    {
        // Opción 1: Usar Livewire Component
        return view('auth.progressive-login');
        
        // Opción 2: Usar vista tradicional con JavaScript
        // return view('auth.progressive-login');
    }

    public function checkUser(Request $request)
    {
        $request->validate([
            'cuit' => 'required|numeric|digits_between:8,11'
        ]);

        $cuit = $request->cuit;
        $ip = $request->ip();

        // Rate limiting básico
        $key = "check-user:{$ip}";
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'exists' => false,
                'message' => 'Demasiados intentos. Intente en 15 minutos.',
                'blocked' => true
            ], 429);
        }

        RateLimiter::hit($key, 15 * 60);

        // Buscar usuario activo
        $user = User::where('username', $cuit)
                   ->where('status', 'active')
                   ->first();

        // Log del intento
        $this->logAttempt($cuit, $ip, 'check_user', $user ? 'success' : 'failed');

        return response()->json([
            'exists' => (bool) $user,
            'message' => $user 
                ? 'Usuario encontrado. Ingrese su contraseña.' 
                : 'CUIT no registrado como usuario activo.',
            'blocked' => false
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'cuit' => 'required|numeric',
            'password' => 'required|string',
        ]);

        $cuit = $request->cuit;
        $password = $request->password;
        $ip = $request->ip();

        // Rate limiting para login
        $loginKey = "login-attempts:{$ip}";
        if (RateLimiter::tooManyAttempts($loginKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos de login. Intente en 15 minutos.',
                'blocked' => true
            ], 429);
        }

        // Buscar usuario
        $user = User::where('username', $cuit)->first();

        if (!$user || $user->status !== 'active') {
            RateLimiter::hit($loginKey, 15 * 60);
            $this->logAttempt($cuit, $ip, 'login', 'failed', ['reason' => 'user_not_found']);
            
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
                'blocked' => false
            ], 401);
        }

        // Verificar si está bloqueado
        if ($user->locked_until && $user->locked_until > now()) {
            $this->logAttempt($cuit, $ip, 'login', 'blocked');
            
            return response()->json([
                'success' => false,
                'message' => 'Usuario temporalmente bloqueado por seguridad.',
                'blocked' => true
            ], 423);
        }

        // Verificar contraseña
        if (!Hash::check($password, $user->password)) {
            $user->increment('failed_login_attempts');
            
            // Bloquear después de 5 intentos
            if ($user->failed_login_attempts >= 5) {
                $user->update([
                    'locked_until' => now()->addMinutes(15),
                    'failed_login_attempts' => 0
                ]);
            }

            RateLimiter::hit($loginKey, 15 * 60);
            $this->logAttempt($cuit, $ip, 'login', 'failed', ['reason' => 'wrong_password']);

            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
                'blocked' => false
            ], 401);
        }

        // Login exitoso
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip
        ]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Limpiar rate limiting
        RateLimiter::clear($loginKey);
        $this->logAttempt($cuit, $ip, 'login', 'success');

        return response()->json([
            'success' => true,
            'message' => '¡Bienvenido! Redirigiendo...',
            'redirect' => route('dashboard')
        ]);
    }

    public function checkCuitForRegistration(Request $request)
    {
        $request->validate([
            'cuit' => 'required|numeric|digits_between:8,11'
        ]);

        $cuit = $request->cuit;
        $ip = $request->ip();

        // Rate limiting para registro
        $regKey = "registration-attempts:{$ip}";
        if (RateLimiter::tooManyAttempts($regKey, 3)) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos de registro. Intente en 30 minutos.',
                'blocked' => true
            ], 429);
        }

        // Verificar si ya existe usuario
        if (User::where('username', $cuit)->exists()) {
            $this->logAttempt($cuit, $ip, 'register_request', 'failed', ['reason' => 'user_exists']);
            
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un usuario con este CUIT. Use "¿Olvidó su contraseña?" para recuperar el acceso.'
            ]);
        }

        // Buscar en proveedores
        $proveedor = Proveedor::on('proveedores')->where('cuit', $cuit)->first();

        if (!$proveedor) {
            RateLimiter::hit($regKey, 30 * 60);
            $this->logAttempt($cuit, $ip, 'register_request', 'failed', ['reason' => 'not_provider']);
            
            return response()->json([
                'success' => false,
                'message' => 'El CUIT no está en nuestro sistema. Puede solicitar registro a través de nuestro formulario de contacto.',
                'show_contact' => true
            ]);
        }

        // Verificar acceso al portal
        if (!$proveedor->portal_access_granted) {
            // Marcar solicitud
            $proveedor->update(['portal_access_requested_at' => now()]);
            
            $this->logAttempt($cuit, $ip, 'register_request', 'failed', ['reason' => 'access_not_granted']);
            
            return response()->json([
                'success' => false,
                'message' => 'Su solicitud de acceso ha sido registrada. Nos pondremos en contacto pronto.',
                'pending_approval' => true
            ]);
        }

        // Crear usuario
        $temporaryPassword = Str::password(12, true, true, true);
        
        $user = User::create([
            'name' => $proveedor->razonsocial,
            'username' => $cuit,
            'email' => $proveedor->correo,
            'password' => Hash::make($temporaryPassword),
            'email_verified_at' => now(),
            'must_change_password' => true,
            'status' => 'active',
            'registered_at' => now(),
            'registration_ip' => $ip
        ]);

        $maskedEmail = $this->maskEmail($proveedor->correo);

        // Enviar email
        try {
            if (str_ends_with($proveedor->correo, '@buenosairesenergia.com.ar')) {
                Mail::to($proveedor->correo)->send(new TemporaryPasswordMail($temporaryPassword));
            }

            $this->logAttempt($cuit, $ip, 'register_request', 'success', [
                'user_id' => $user->id,
                'email_sent' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se ha enviado una contraseña temporal a {$maskedEmail}"
            ]);

        } catch (\Exception $e) {
            $this->logAttempt($cuit, $ip, 'register_request', 'success', [
                'user_id' => $user->id,
                'email_sent' => false,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Usuario creado. Se enviará la contraseña temporal a {$maskedEmail}"
            ]);
        }
    }

    private function logAttempt(string $username, string $ip, string $type, string $result, array $metadata = []): void
    {
        try {
            LoginAttempt::create([
                'username' => $username,
                'ip_address' => $ip,
                'attempt_type' => $type,
                'result' => $result,
                'user_agent' => request()->userAgent(),
                'metadata' => $metadata
            ]);
        } catch (\Exception $e) {
            // Si falla el log, no queremos que afecte el flujo principal
            Log::error('Failed to log login attempt', [
                'username' => $username,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function maskEmail(string $email): string
    {
        [$username, $domain] = explode('@', $email);
        
        if (strlen($username) <= 4) {
            return $email;
        }
        
        $visibleStart = substr($username, 0, 2);
        $visibleEnd = substr($username, -2);
        $masked = $visibleStart . str_repeat('x', strlen($username) - 4) . $visibleEnd;
        
        return "{$masked}@{$domain}";
    }
}