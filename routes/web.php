<?php

use App\Http\Controllers\Auth\CustomLoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\ProgressiveLoginController; // NUEVO
use App\Http\Controllers\Auth\ProviderRegistrationController;
use App\Http\Controllers\ConcursoController;
use App\Http\Controllers\DataRequestController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\FileController;
use App\Mail\RegistroProveedor;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

Livewire::setUpdateRoute(function ($handle) {
    return Route::post('portalproveedores/public/livewire/update', $handle);
});
Livewire::setScriptRoute(function ($handle) {
    return Route::get('portalproveedores/public/livewire/livewire.js', $handle);
});

// Ruta principal
Route::get('/', function () {
    return redirect()->route('login');
});

// ============================================
// RUTAS PÚBLICAS (sin autenticación)
// ============================================
Route::middleware(['guest'])->group(function () {
    
    // === LOGIN PROGRESIVO (NUEVO SISTEMA) ===
    Route::get('/login', [ProgressiveLoginController::class, 'showLoginForm'])
        ->name('login');
    
    // Rutas API para el login progresivo
    Route::prefix('auth')->group(function () {
        Route::post('/check-user', [ProgressiveLoginController::class, 'checkUser'])
            ->name('auth.check-user');
        Route::post('/login', [ProgressiveLoginController::class, 'login'])
            ->name('auth.login');
        Route::post('/check-registration', [ProgressiveLoginController::class, 'checkCuitForRegistration'])
            ->name('auth.check-registration');
    });

    // === FORMULARIO DE REGISTRO ===
    Route::get('/bienvenido', function () {
        return view('bienvenido');
    })->name('bienvenido');
    Route::post('/recibidos', function (Request $request) {
        $datos = $request->all();
        //Mail::to(['infoproveedores@buenosairesenergia.com.ar', $datos['correo_personal']])->send(new RegistroProveedor($datos)); 
        return redirect()->route('gracias');
    })->name('recibidos');
    Route::get('/gracias', function () {
        return view('gracias');
    })->name('gracias');

    // === RUTAS DE RESPALDO (si quieres mantener el sistema anterior temporalmente) ===
    /* Route::prefix('legacy')->group(function () {
        Route::get('/login', [CustomLoginController::class, 'showLoginForm'])
            ->name('legacy.login');
        Route::post('/login', [CustomLoginController::class, 'login'])
            ->name('legacy.login.post');
        
        // Registro legacy
        Route::get('/register-provider', [ProviderRegistrationController::class, 'showRegistrationForm'])
            ->name('legacy.provider.register');
        Route::post('/check-cuit', [ProviderRegistrationController::class, 'checkCuit'])
            ->name('legacy.provider.check-cuit');
    }); */

    // === RECUPERACIÓN DE CONTRASEÑA ===
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->name('password.update');
});

// ============================================
// RUTAS AUTENTICADAS
// ============================================
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'force.password.change'
])->group(function () {

    /* Route::get('/login', function () {
        dd('login');
    })->name('login'); */
    
    // Dashboard principal
    Route::get('/dashboard', [ProveedorController::class, 'dashboard'])
        ->name('dashboard');

    // Concursos
    Route::get('/concursos', [ConcursoController::class, 'index'])->name('concursos.index');
    Route::get('/concursos/{id}', [ConcursoController::class, 'show'])->name('concursos.show');
    Route::patch('/concursos/{id}/intencion', [ConcursoController::class, 'cambiarIntencion'])->name('concursos.intencion');
    
    // Logout
    Route::post('/logout', [CustomLoginController::class, 'logout'])
        ->name('logout');
    
    // Validación de sesión
    Route::get('/validate-session', [CustomLoginController::class, 'validateSession'])
        ->name('validate.session');

    // === GESTIÓN DE ARCHIVOS ===
    Route::prefix('files')->name('file.')->group(function () {
        // Subida de archivos
        Route::post('/upload/{concurso}', [FileController::class, 'uploadFileToPlataforma'])
            ->name('upload');
        Route::post('/upload-apoderado', [FileController::class, 'uploadDocumentacionApoderado'])
            ->name('uploadDocumentacionApoderado');
        
        // Descarga y eliminación
        Route::post('/download', [FileController::class, 'downloadFileFromPlataforma'])
            ->name('download');
        Route::post('/download-proveedor-documento', [FileController::class, 'downloadProveedorDocumento'])
            ->name('download-proveedor-documento');
        Route::post('/download-concurso-documento', [FileController::class, 'downloadConcursoDocumento'])
            ->name('download-concurso-documento');
        Route::post('/delete', [FileController::class, 'deleteFileFromPlataforma'])
            ->name('delete');
        
        // API de concursos
        Route::get('/concursos/{concurso}/documentos', [FileController::class, 'getDocumentosInvitacion'])
            ->name('concursos.documentos');
        Route::get('/concursos/{concurso}/documentos/{documentoTipo}/verificar', [FileController::class, 'verificarDocumentoProveedor'])
            ->name('concursos.verificar-documento');
    });

    // === GESTIÓN DE INVITACIONES ===
    Route::prefix('invitaciones')->name('data-request.')->group(function () {
        Route::post('/editar', [DataRequestController::class, 'editarInvitacion'])
            ->name('editar-invitacion');
        Route::post('/baja', [DataRequestController::class, 'bajarOferta'])
            ->name('bajar-oferta');
    });
});

// ============================================
// CAMBIO DE CONTRASEÑA (sin middleware force.password.change)
// ============================================
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/change-password', [PasswordChangeController::class, 'showChangeForm'])
        ->name('password.change');
    Route::post('/change-password', [PasswordChangeController::class, 'update'])
        ->name('password.change.update');
});

// ============================================
// RUTAS DE ADMINISTRACIÓN (OPCIONAL)
// ============================================
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->prefix('admin')->name('admin.')->group(function () {
    
    // Solo para usuarios administrativos (puedes agregar middleware específico)
    Route::get('/login-attempts', function () {
        return view('admin.login-attempts');
    })->name('login-attempts');
    
    Route::get('/portal-access-requests', function () {
        return view('admin.portal-access-requests');
    })->name('portal-access-requests');
});

// ============================================
// RUTAS DE DESARROLLO/TESTING (remover en producción)
// ============================================
if (app()->environment(['local', 'testing'])) {
    Route::prefix('dev')->group(function () {
        Route::get('/test-login', function () {
            return view('livewire.auth.progressive-login');
        })->name('dev.test-login');
        
        Route::get('/test-emails', function () {
            $email = 'test@buenosairesenergia.com.ar';
            $maskedEmail = function($email) {
                [$username, $domain] = explode('@', $email);
                if (strlen($username) <= 4) return $email;
                $visibleStart = substr($username, 0, 2);
                $visibleEnd = substr($username, -2);
                $masked = $visibleStart . str_repeat('x', strlen($username) - 4) . $visibleEnd;
                return "{$masked}@{$domain}";
            };
            
            return response()->json([
                'original' => $email,
                'masked' => $maskedEmail($email)
            ]);
        })->name('dev.test-emails');
    });
}