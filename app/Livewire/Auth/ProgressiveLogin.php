<?php
// app/Livewire/Auth/ProgressiveLogin.php
namespace App\Livewire\Auth;

use App\Mail\PasswordResetMail;
use App\Models\LoginAttempt;
use App\Models\Proveedores\Proveedor;
use App\Models\User;
use App\Mail\TemporaryPasswordMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class ProgressiveLogin extends Component
{
    public string $cuit = '';
    public string $password = '';
    public string $step = 'initial'; // initial, loading, user_found, user_internal_only, user_not_found
    public array $message = ['type' => '', 'text' => ''];

    // Propiedades para el modal de recuperación
    public bool $showPasswordRecoveryModal = false;
    public string $recoveryCuit = '';
    public bool $recoveryEmailSent = false;

    protected $rules = [
        'cuit' => 'required|string|min:8|max:15|regex:/^[0-9]+$/',
        'password' => 'nullable|string|min:6',
        'recoveryCuit' => 'required|string|min:8|max:15|regex:/^[0-9]+$/'
    ];

    protected $messages = [
        'cuit.required' => 'El CUIT es obligatorio.',
        'cuit.regex' => 'El CUIT debe contener solo números.',
        'cuit.min' => 'El CUIT debe tener al menos 8 dígitos.',
        'cuit.max' => 'El CUIT debe tener máximo 15 dígitos.',
        'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        'recoveryCuit.required' => 'El CUIT es obligatorio.',
        'recoveryCuit.regex' => 'El CUIT debe contener solo números.',
        'recoveryCuit.min' => 'El CUIT debe tener al menos 8 dígitos.',
        'recoveryCuit.max' => 'El CUIT debe tener máximo 15 dígitos.'
    ];

    public function mount()
    {
        $this->resetState();
    }

    public function updatedCuit()
    {
        // Reset cuando cambia el CUIT
        if ($this->step !== 'initial') {
            $this->resetToInitial();
        }
    }

    // ACCIÓN: Mostrar modal de recuperación
    public function showPasswordRecovery()
    {
        $this->showPasswordRecoveryModal = true;
        $this->recoveryCuit = $this->cuit; // Ya tenemos el CUIT validado del paso anterior
        $this->recoveryEmailSent = false;
    }

    // ACCIÓN: Cerrar modal de recuperación
    public function closePasswordRecoveryModal()
    {
        $this->showPasswordRecoveryModal = false;
        $this->recoveryCuit = '';
        $this->recoveryEmailSent = false;
    }

    // ACCIÓN: Enviar enlace de recuperación
    public function sendPasswordRecoveryLink()
    {
        // Ya no validamos porque el CUIT ya está validado desde el paso anterior
        if ($this->isRateLimited('password_recovery')) {
            return;
        }

        // Usar el CUIT que ya tenemos del paso anterior
        $user = User::where('username', $this->cuit)->first();

        if (!$user) {
            // Esto no debería pasar, pero por seguridad
            $this->addError('general', 'Error interno. Intente nuevamente.');
            return;
        }
        
        // Buscar el correo actual del proveedor
        $proveedor = Proveedor::on('proveedores')->where('cuit', $this->cuit)->first();
        if (!$proveedor) {
            $this->addError('general', 'Error interno al buscar el proveedor, contáctese con la empresa.');
            return;
        }

        if($user->email != $proveedor->correo) {
            $user->email = $proveedor->correo;
            $user->save();
        }

        // Generar token único
        $token = Str::random(64);

        // Guardar el token en la base de datos
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'username' => $user->username,
            'created_at' => Carbon::now()
        ]);

        // Enviar el email con el link de reseteo
        try {
            if(app()->environment('production') || str_ends_with($user->email, '@buenosairesenergia.com.ar') || $user->email == 'nachofernan@gmail.com') {
                Mail::to([$user->email])->send(new PasswordResetMail($token, $user));
            }

            $this->logAttempt('password_recovery', 'success', [
                'user_id' => $user->id,
                'email_sent' => true
            ]);

            $this->recoveryEmailSent = true;
            
            // Cerrar modal después de 2 segundos y mostrar mensaje
            $this->dispatch('recovery-email-sent');

        } catch (\Exception $e) {
            $this->logAttempt('password_recovery', 'failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            $this->addError('general', 'Error al enviar el correo. Intente nuevamente.');
        }
    }

    // ACCIÓN: Buscar usuario
    public function searchUser()
    {
        if (empty(trim($this->cuit))) {
            $this->showMessage('error', 'Por favor ingrese su CUIT.');
            return;
        }

        $this->validate(['cuit' => $this->rules['cuit']]);
        
        if ($this->isRateLimited()) {
            return;
        }

        $this->step = 'loading';
        $this->clearMessage();

        // CASO 1: Buscar usuario existente
        $user = User::where('username', $this->cuit)->first();

        if ($user) {
            // Usuario ya registrado - pedir contraseña
            $this->handleUserFound();
            return;
        }

        // CASO 2: Buscar en proveedores internos
        $proveedor = Proveedor::on('proveedores')->where('cuit', $this->cuit)->first();

        if ($proveedor) {
            // Existe en base interna pero no como usuario
            $this->handleUserInternalOnly($proveedor);
            return;
        }

        // CASO 3: No existe en ninguna base
        $this->handleUserNotFound();
    }

    // ACCIÓN: Intentar login
    public function attemptLogin()
    {
        $this->validate([
            'cuit' => 'required',
            'password' => 'required'
        ]);

        if ($this->isRateLimited('login')) {
            return;
        }

        $this->step = 'loading';
        $this->clearMessage();

        $user = User::where('username', $this->cuit)->first();

        if (!$user) {
            $this->handleFailedLogin('user_not_found');
            return;
        }

        if ($user->isLocked()) {
            $this->logAttempt('login', 'blocked');
            $this->step = 'user_found';
            $this->showMessage('error', 'Usuario temporalmente bloqueado por seguridad. Intente más tarde.');
            return;
        }

        if (!Hash::check($this->password, $user->password)) {
            $this->handleFailedLogin('wrong_password', $user);
            return;
        }

        // Login exitoso
        $this->handleSuccessfulLogin($user);
    }

    // ACCIÓN: Enviar contraseña provisoria
    public function sendTemporaryPassword()
    {
        $this->validate(['cuit' => $this->rules['cuit']]);

        if ($this->isRateLimited('registration')) {
            return;
        }

        $this->step = 'loading';
        $this->clearMessage();

        // Verificar que no exista usuario
        if (User::where('username', $this->cuit)->exists()) {
            $this->logAttempt('register_request', 'failed', ['reason' => 'user_exists']);
            $this->showMessage('error', 'Ya existe un usuario con este CUIT.');
            $this->resetToInitial();
            return;
        }

        // Buscar proveedor
        $proveedor = Proveedor::on('proveedores')->where('cuit', $this->cuit)->first();

        if (!$proveedor) {
            $this->logAttempt('register_request', 'failed', ['reason' => 'provider_not_found']);
            $this->showMessage('error', 'Error al procesar la solicitud.');
            $this->resetToInitial();
            return;
        }

        // Crear usuario y enviar contraseña
        $this->createUserForProvider($proveedor);
    }

    // === MANEJADORES DE CASOS ===

    private function handleUserFound()
    {
        $this->step = 'user_found';
        $this->logAttempt('check_user', 'success');
        $this->showMessage('success', 'Usuario encontrado. Ingrese su contraseña.');
        $this->dispatch('focus-password');
    }

    private function handleUserInternalOnly(Proveedor $proveedor)
    {
        $this->step = 'user_internal_only';
        $this->logAttempt('check_user', 'found_internal');
        
        $maskedEmail = $this->maskEmail($proveedor->correo);
        $this->showMessage('info', "Su CUIT está registrado en nuestro sistema. Puede solicitar una contraseña provisoria que será enviada a {$maskedEmail}");
    }

    private function handleUserNotFound()
    {
        $this->step = 'user_not_found';
        $this->incrementAttempts();
        $this->logAttempt('check_user', 'not_found');
        $this->showMessage('info', 'El CUIT no está registrado en nuestro sistema.');
    }

    // === MÉTODOS DE APOYO ===

    private function createUserForProvider(Proveedor $proveedor)
    {
        $temporaryPassword = Str::random(10);
        
        try {
            $user = User::create([
                'name' => $proveedor->razonsocial,
                'username' => $this->cuit,
                'email' => $proveedor->correo,
                'password' => Hash::make($temporaryPassword),
                'email_verified_at' => now(),
                'must_change_password' => true,
                'status' => 'active',
                'registered_at' => now(),
                'registration_ip' => request()->ip()
            ]);

            $maskedEmail = $this->maskEmail($proveedor->correo);

            // Enviar email solo a dominios autorizados
            if(app()->environment('production') || str_ends_with($user->email, '@buenosairesenergia.com.ar') || $user->email == 'nachofernan@gmail.com') {
                Mail::to($proveedor->correo)->send(new TemporaryPasswordMail($temporaryPassword));
            }

            $this->logAttempt('register_request', 'success', [
                'user_id' => $user->id,
                'email_sent' => true
            ]);

            $this->resetToInitial();
            $this->showMessage('success', "Usuario creado exitosamente. Se ha enviado una contraseña provisoria a {$maskedEmail}");

        } catch (\Exception $e) {
            $this->logAttempt('register_request', 'failed', ['error' => $e->getMessage()]);
            $this->resetToInitial();
            $this->showMessage('error', 'Error al crear el usuario. Por favor intente nuevamente.');
        }
    }

    private function handleSuccessfulLogin(User $user)
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => request()->ip()
        ]);

        Auth::login($user, true);
        $this->logAttempt('login', 'success');
        $this->clearAttempts();

        // GUARDAR EL TOKEN JWT EN LA SESIÓN
        try {
            $token = app(\App\Http\Controllers\AuthController::class)->getNewToken();
            session(['jwt_token' => $token]);
            Log::info('JWT token guardado en sesión (Livewire)', ['user_id' => $user->id, 'token' => $token]);
        } catch (\Exception $e) {
            Log::error('No se pudo obtener el token JWT (Livewire)', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        $this->showMessage('success', '¡Bienvenido! Redirigiendo...');
        $this->dispatch('redirect-to', route('dashboard'));
    }

    private function handleFailedLogin(string $reason, User $user = null)
    {
        if ($user) {
            $user->increment('failed_login_attempts');
            
            if ($user->failed_login_attempts >= 5) {
                $user->update([
                    'locked_until' => now()->addMinutes(15),
                    'failed_login_attempts' => 0
                ]);
            }
        }

        $this->logAttempt('login', 'failed', ['reason' => $reason]);
        $this->incrementAttempts('login');
        
        $this->step = 'user_found';
        $this->password = '';
        $this->showMessage('error', 'Credenciales incorrectas. Intente nuevamente.');
    }

    // === UTILIDADES ===

    private function isRateLimited(string $type = 'search'): bool
    {
        $key = "{$type}:" . request()->ip();
        $maxAttempts = $type === 'registration' ? 3 : 10;
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->showMessage('error', 'Demasiados intentos. Intente nuevamente en unos minutos.');
            $this->step = 'initial';
            return true;
        }
        
        return false;
    }

    private function incrementAttempts(string $type = 'search'): void
    {
        $key = "{$type}:" . request()->ip();
        RateLimiter::hit($key, 15 * 60);
    }

    private function clearAttempts(string $type = 'login'): void
    {
        $key = "{$type}:" . request()->ip();
        RateLimiter::clear($key);
    }

    private function logAttempt(string $type, string $result, array $metadata = []): void
    {
        try {
            LoginAttempt::create([
                'username' => $this->cuit,
                'ip_address' => request()->ip(),
                'attempt_type' => $type,
                'result' => $result,
                'user_agent' => request()->userAgent(),
                'metadata' => $metadata
            ]);
        } catch (\Exception $e) {
            // Log silencioso
        }
    }

    private function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return $email;
        }
        
        [$username, $domain] = explode('@', $email);
        
        if (strlen($username) <= 4) {
            return $email;
        }
        
        $visibleStart = substr($username, 0, 2);
        $visibleEnd = substr($username, -2);
        $masked = $visibleStart . str_repeat('*', strlen($username) - 4) . $visibleEnd;
        
        return "{$masked}@{$domain}";
    }

    private function resetToInitial(): void
    {
        $this->step = 'initial';
        $this->password = '';
        $this->clearMessage();
    }

    public function resetState(): void
    {
        $this->step = 'initial';
        $this->cuit = '';
        $this->password = '';
        $this->clearMessage();
    }

    private function showMessage(string $type, string $text): void
    {
        $this->message = ['type' => $type, 'text' => $text];
    }

    private function clearMessage(): void
    {
        $this->message = ['type' => '', 'text' => ''];
    }

    public function render()
    {
        return view('livewire.auth.progressive-login');
    }
}
