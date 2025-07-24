<?php

namespace App\Http\Controllers;

use App\Models\Concursos\Invitacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DataRequestController extends Controller
{
    protected $authController;

    public function __construct(AuthController $authController)
    {
        $this->authController = $authController;
    }

    public function editarInvitacion(Request $request)
    {
        // ✅ Validación robusta
        $validated = $request->validate([
            'invitacion' => 'required|integer',
            'intencion' => 'required|integer' // Solo valores permitidos
        ]);

        try {
            $token = $this->authController->getNewToken();
            
            $response = Http::timeout(15)
                ->withToken($token)
                ->post($this->getApiUrl() . '/invitacion', [
                    'invitacion' => $validated['invitacion'],
                    'intencion' => $validated['intencion'],
                ]);

            if (!$response->successful()) {
                Log::error('Invitation edit failed', [
                    'user_id' => Auth::id(),
                    'invitacion_id' => $validated['invitacion'],
                    'status' => $response->status()
                ]);
                
                return back()->with('error', 'Error al actualizar la invitación. Intente nuevamente.');
            }

            $invitacion = Invitacion::find($validated['invitacion']);
            
            Log::info('Invitation updated successfully', [
                'user_id' => Auth::id(),
                'invitacion_id' => $validated['invitacion'],
                'new_intencion' => $validated['intencion']
            ]);

            return redirect()->route('concursos.show', $invitacion->concurso->id)
                ->with('success', 'Invitación actualizada correctamente.');

        } catch (\Exception $e) {
            Log::error('Invitation edit exception', [
                'user_id' => Auth::id(),
                'invitacion_id' => $validated['invitacion'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Error temporal del sistema. Intente nuevamente.');
        }
    }

    public function bajarOferta(Request $request)
    {
        // ✅ Validación más estricta
        $validated = $request->validate([
            'invitacion' => 'required|integer',
            'intencion' => 'required|integer', // Solo valor 1 permitido
            'password' => 'required|string' // Validar contraseña
        ]);

        try {
            // ✅ Verificar contraseña del usuario actual
            if (!\Illuminate\Support\Facades\Hash::check($validated['password'], Auth::user()->password)) {
                Log::warning('Invalid password attempt for offer withdrawal', [
                    'user_id' => Auth::id(),
                    'invitacion_id' => $validated['invitacion']
                ]);
                
                return back()->with('error', 'Contraseña incorrecta.');
            }

            $invitacion = Invitacion::with('documentos')->find($validated['invitacion']);

            if (!$invitacion) {
                return back()->with('error', 'Invitación no encontrada.');
            }

            // ✅ Verificar que el usuario puede editar esta invitación
            if ($invitacion->proveedor_id !== Auth::user()->proveedor->id) {
                Log::warning('Unauthorized offer withdrawal attempt', [
                    'user_id' => Auth::id(),
                    'invitacion_id' => $validated['invitacion']
                ]);
                
                return back()->with('error', 'No tiene permisos para esta acción.');
            }

            $token = $this->authController->getNewToken();

            // Eliminar archivos de la plataforma
            foreach ($invitacion->documentos as $documento) {
                try {
                    $response = Http::timeout(15)
                        ->withToken($token)
                        ->get($this->getApiUrl() . '/delete', [
                            'filename' => $documento->file_storage,
                            'disk' => 'concursos'
                        ]);
                    
                    // ✅ Log individual de cada eliminación
                    if (!$response->successful()) {
                        Log::warning('Document deletion failed during offer withdrawal', [
                            'user_id' => Auth::id(),
                            'document_id' => $documento->id,
                            'status' => $response->status()
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    Log::error('Document deletion exception during offer withdrawal', [
                        'user_id' => Auth::id(),
                        'document_id' => $documento->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continuar con otros archivos en caso de error
                }
            }

            // Restablecer la invitación
            $response = Http::timeout(15)
                ->withToken($token)
                ->post($this->getApiUrl() . '/invitacion', [
                    'invitacion' => $invitacion->id,
                    'intencion' => 1,
                ]);

            if (!$response->successful()) {
                Log::error('Invitation reset failed during offer withdrawal', [
                    'user_id' => Auth::id(),
                    'invitacion_id' => $invitacion->id,
                    'status' => $response->status()
                ]);
                
                return back()->with('error', 'Error al procesar la baja de oferta. Contacte al administrador.');
            }

            Log::info('Offer withdrawn successfully', [
                'user_id' => Auth::id(),
                'invitacion_id' => $invitacion->id,
                'documents_count' => $invitacion->documentos->count()
            ]);

            return back()->with('success', 'Oferta dada de baja correctamente.');

        } catch (\Exception $e) {
            Log::error('Offer withdrawal exception', [
                'user_id' => Auth::id(),
                'invitacion_id' => $validated['invitacion'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Error temporal del sistema. Intente nuevamente.');
        }
    }

    // ✅ URL centralizada
    private function getApiUrl(): string
    {
        $url = env('PLATAFORMA_API_URL');
        
        if (empty($url)) {
            Log::critical('PLATAFORMA_API_URL not configured');
            throw new \Exception('Configuración del sistema incompleta');
        }

        return rtrim($url, '/');
    }
}