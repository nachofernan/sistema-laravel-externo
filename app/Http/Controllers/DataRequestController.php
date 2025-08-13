<?php

namespace App\Http\Controllers;

use App\Models\Concursos\Invitacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\ConcursosApiService;

class DataRequestController extends Controller
{
    protected $authController;

    public function __construct(AuthController $authController)
    {
        $this->authController = $authController;
    }

    /**
     * Cambiar intención de participación usando la API de concursos
     */
    public function editarInvitacion(Request $request)
    {
        // ✅ Validación robusta
        $validated = $request->validate([
            'concurso_id' => 'required|integer|min:1',
            'intencion' => 'required|integer|in:0,1,2,3' // Solo valores permitidos
        ]);

        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            
            $success = $api->cambiarIntencion($validated['concurso_id'], $validated['intencion']);

            if ($success) {
                Log::info('Invitation updated successfully', [
                    'user_id' => Auth::id(),
                    'concurso_id' => $validated['concurso_id'],
                    'new_intencion' => $validated['intencion']
                ]);

                return redirect()->route('concursos.show', $validated['concurso_id'])
                    ->with('success', 'Invitación actualizada correctamente.');
            } else {
                Log::error('Invitation edit failed', [
                    'user_id' => Auth::id(),
                    'concurso_id' => $validated['concurso_id'],
                    'intencion' => $validated['intencion']
                ]);
                
                return redirect()->route('concursos.show', $validated['concurso_id'])
                    ->with('error', 'Error al actualizar la invitación. Intente nuevamente.');
            }

        } catch (\Exception $e) {
            Log::error('Invitation edit exception', [
                'user_id' => Auth::id(),
                'concurso_id' => $validated['concurso_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('concursos.show', $validated['concurso_id'] ?? 1)
                ->with('error', 'Error temporal del sistema. Intente nuevamente.');
        }
    }

    /**
     * Bajar oferta (marcar como no participa)
     */
    public function bajarOferta(Request $request)
    {
        $validated = $request->validate([
            'concurso_id' => 'required|integer|min:1',
        ]);

        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            
            // Marcar como no participa (intencion = 2)
            $success = $api->cambiarIntencion($validated['concurso_id'], 2);

            if ($success) {
                Log::info('Oferta bajada successfully', [
                    'user_id' => Auth::id(),
                    'concurso_id' => $validated['concurso_id']
                ]);

                return redirect()->route('concursos.show', $validated['concurso_id'])
                    ->with('success', 'Oferta bajada correctamente.');
            } else {
                return redirect()->route('concursos.show', $validated['concurso_id'])
                    ->with('error', 'Error al bajar la oferta. Intente nuevamente.');
            }

        } catch (\Exception $e) {
            Log::error('Bajar oferta exception', [
                'user_id' => Auth::id(),
                'concurso_id' => $validated['concurso_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('concursos.show', $validated['concurso_id'] ?? 1)
                ->with('error', 'Error temporal del sistema. Intente nuevamente.');
        }
    }
}