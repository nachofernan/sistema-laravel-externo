<?php

namespace App\Http\Controllers;

use App\Models\Concursos\Invitacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\ConcursosApiService;
use App\Services\ProveedorApiService;


class FileController extends Controller
{
    protected $authController;

    public function __construct(AuthController $authController)
    {
        $this->authController = $authController;
    }

    /**
     * Subir documento de concurso usando la API de concursos
     */
    public function uploadFileToPlataforma(Request $request, int $concursoId)
    {
        // ✅ Validación centralizada y robusta
        $this->validateUploadFile($request);

        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            
            $file = $request->file('file');
            $documentoTipoId = $request->input('documento_tipo_id');
            
            // ✅ Sanitizar nombre del archivo
            $sanitizedName = $this->sanitizeFileName($file->getClientOriginalName());
            
            // ✅ Validar contenido del archivo (básico)
            if (!$this->isValidPdfFile($file)) {
                return back()->with('error', 'El archivo no parece ser un PDF válido.');
            }

            // Usar la API de concursos según documentación
            $result = $api->subirDocumentoConcurso($concursoId, $file, $documentoTipoId);

            if ($result) {
                Log::info('File uploaded successfully', [
                    'user_id' => Auth::id(),
                    'concurso_id' => $concursoId,
                    'documento_tipo_id' => $documentoTipoId,
                    'file_name' => $sanitizedName
                ]);
                return back()->with('success', 'Archivo subido exitosamente.');
            }

            // ✅ Log del error sin exponer detalles
            Log::error('File upload failed', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId,
                'documento_tipo_id' => $documentoTipoId
            ]);

            return back()->with('error', 'Error al procesar el archivo. Intente nuevamente.');

        } catch (\Exception $e) {
            Log::error('File upload exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Error temporal del sistema. Intente nuevamente.');
        }
    }

    /**
     * Descargar documento de concurso usando la API de concursos
     */
    public function downloadConcursoDocumento(Request $request)
    {
        $validated = $request->validate([
            'concurso_id' => 'required|integer|min:1',
            'documento_id' => 'required|integer|min:1',
        ]);
        
        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            
            return $api->descargarDocumentoConcursoResponse(
                $validated['concurso_id'], 
                $validated['documento_id']
            );
            
        } catch (\Exception $e) {
            Log::error('Concurso document download exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'concurso_id' => $validated['concurso_id'] ?? 'unknown',
                'documento_id' => $validated['documento_id'] ?? 'unknown'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el documento. Intente nuevamente.'
            ], 500);
        }
    }

    /**
     * Descargar documento de proveedor usando la API
     */
    public function downloadProveedorDocumento(Request $request)
    {
        $validated = $request->validate([
            'documento_id' => 'required|integer|min:1',
        ]);
        
        try {
            $proveedorService = new \App\Services\ProveedorApiService();
            return $proveedorService->descargarDocumentoResponse($validated['documento_id']);
            
        } catch (\Exception $e) {
            Log::error('Proveedor document download exception', [
                'user_id' => Auth::id(),
                'documento_id' => $validated['documento_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el documento. Intente nuevamente.'
            ], 500);
        }
    }

    /**
     * Obtener documentos de la invitación usando la API de concursos
     */
    public function getDocumentosInvitacion(int $concursoId)
    {
        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            
            $documentos = $api->getDocumentosInvitacion($concursoId);
            
            return response()->json([
                'success' => true,
                'data' => $documentos
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get documentos invitación exception', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener documentos.'
            ], 500);
        }
    }

    /**
     * Verificar documento de proveedor usando la API de concursos
     */
    public function verificarDocumentoProveedor(int $concursoId, int $documentoTipoId)
    {
        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            
            $documento = $api->verificarDocumentoProveedor($concursoId, $documentoTipoId);
            
            if ($documento) {
                return response()->json([
                    'success' => true,
                    'data' => $documento
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay documento válido para este tipo.'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Verificar documento proveedor exception', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId,
                'documento_tipo_id' => $documentoTipoId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar documento.'
            ], 500);
        }
    }

    public function uploadDocumentacionApoderado(Request $request)
    {
        $this->validateUploadFile($request, ['tipo' => 'required|in:apoderado,representante']);

        try {
            $token = $this->authController->getNewToken();
            $file = $request->file('file');
            $sanitizedName = $this->sanitizeFileName($file->getClientOriginalName());
            
            if (!$this->isValidPdfFile($file)) {
                return back()->with('error', 'El archivo no parece ser un PDF válido.');
            }

            $response = Http::timeout(30)
                ->withToken($token)
                ->attach('file', file_get_contents($file->getPathname()), $sanitizedName)
                ->post($this->getApiUrl() . '/uploadDocumentacionApoderado', [
                    'proveedor_id' => $request->input('proveedor_id'),
                    'vencimiento' => $request->input('vencimiento') ?? null,
                    'nombre' => $request->input('nombre') ?? null,
                    'tipo' => $request->input('tipo'),
                ]);

            if ($response->successful()) {
                return back()->with('success', 'Documentación de apoderado subida exitosamente.');
            }

            return back()->with('error', 'Error al procesar la documentación. Intente nuevamente.');

        } catch (\Exception $e) {
            Log::error('Apoderado document upload exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Error temporal del sistema. Intente nuevamente.');
        }
    }

    public function deleteFileFromPlataforma(Request $request)
    {
        $validated = $request->validate([
            'fileName' => 'required|string|max:255',
            'disk' => 'required|string|in:concursos,proveedores'
        ]);
        
        try {
            $token = $this->authController->getNewToken();
            
            $response = Http::timeout(15)
                ->withToken($token)
                ->get($this->getApiUrl() . '/delete', [
                    'filename' => $validated['fileName'],
                    'disk' => $validated['disk']
                ]);

            if ($response->successful()) {
                Log::info('File deleted successfully', [
                    'user_id' => Auth::id(),
                    'file' => $validated['fileName']
                ]);
            }
                
            return back();
            
        } catch (\Exception $e) {
            Log::error('File deletion exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Error al eliminar el archivo.');
        }
    }

    // ✅ Validación centralizada de archivos
    private function validateUploadFile(Request $request, array $extraRules = []): void
    {
        $rules = array_merge([
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240', // 10MB
                function ($attribute, $value, $fail) {
                    // ✅ Validar que el archivo no esté corrupto
                    if (!$value->isValid()) {
                        $fail('El archivo está corrupto o es inválido.');
                    }
                },
            ]
        ], $extraRules);

        $request->validate($rules);
    }

    // ✅ Sanitizar nombre de archivo
    private function sanitizeFileName(string $filename): string
    {
        // Remover caracteres peligrosos
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limitar longitud
        if (strlen($filename) > 100) {
            $info = pathinfo($filename);
            $name = substr($info['filename'], 0, 90);
            $filename = $name . '.' . ($info['extension'] ?? 'pdf');
        }
        
        return $filename;
    }

    // ✅ Validación básica de contenido PDF
    private function isValidPdfFile($file): bool
    {
        // Leer los primeros bytes para verificar header PDF
        $handle = fopen($file->getPathname(), 'rb');
        $header = fread($handle, 4);
        fclose($handle);
        
        return $header === '%PDF';
    }

    // ✅ URL centralizada
    private function getApiUrl(): string
    {
        $url = env('PLATAFORMA_API_URL');
        
        if (empty($url)) {
            throw new \Exception('Configuración del sistema incompleta');
        }

        return rtrim($url, '/');
    }

    public function uploadDocumentoGeneral(Request $request)
    {
        // 1. Validación clásica
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
            'documento_tipo_id' => 'required',
            'vencimiento' => 'nullable|date',
        ]);

        try {
            $user = Auth::user();
            // Usamos el servicio de proveedores
            $api = new ProveedorApiService(); 
            
            $file = $request->file('file');
            
            // 2. Llamada a la API (reutilizando tu lógica de sanitización si querés)
            $result = $api->subirDocumento(
                $file, 
                (int)$request->input('documento_tipo_id'), 
                $request->input('vencimiento')
            );

            if ($result) {
                return back()->with('success', 'Documento cargado con éxito.');
            }

            return back()->with('error', 'La API no pudo procesar el documento.');

        } catch (\Exception $e) {
            Log::error('Error en uploadDocumentoGeneral: ' . $e->getMessage());
            return back()->with('error', 'Error de conexión con el servidor de archivos.');
        }
    }

    public function uploadApoderado(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
            'tipo' => 'required|in:apoderado,representante',
            'nombre' => 'required_if:tipo,representante|nullable|string',
            'vencimiento' => 'nullable|date',
        ]);

        try {
            $api = new ProveedorApiService();
            
            $result = $api->subirApoderado(
                $request->file('file'),
                $request->input('tipo'),
                $request->input('nombre'),
                $request->input('vencimiento')
            );

            if ($result) {
                return back()->with('success', 'Apoderado/Representante cargado con éxito.');
            }

            return back()->with('error', 'La API no pudo procesar la carga del apoderado.');

        } catch (\Exception $e) {
            Log::error('Error en uploadApoderado: ' . $e->getMessage());
            return back()->with('error', 'Error de comunicación al subir apoderado.');
        }
    }

    public function uploadConcursoFile(Request $request, $concursoId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
            'documento_tipo_id' => 'nullable', // Permitimos que venga vacío
        ]);

        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);

            // Convertimos a int solo si tiene valor, sino null
            $docId = $request->filled('documento_tipo_id') ? (int)$request->input('documento_tipo_id') : null;

            $result = $api->subirDocumentoConcurso(
                (int)$concursoId,
                $request->file('file'),
                $docId // Aquí ya puede ir null sin que PHP chille
            );

            if ($result) {
                return back()->with('success', 'Archivo cargado correctamente.');
            }

            return back()->with('error', 'La API no pudo procesar el archivo.');

        } catch (\Exception $e) {
            Log::error('Error en uploadConcursoFile: ' . $e->getMessage());
            return back()->with('error', 'Error crítico al subir archivo.');
        }
    }
}