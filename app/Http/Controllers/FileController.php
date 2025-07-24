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

class FileController extends Controller
{
    protected $authController;

    public function __construct(AuthController $authController)
    {
        $this->authController = $authController;
    }

    public function uploadFileToPlataforma(Request $request, Invitacion $invitacion)
    {
        // ✅ Validación centralizada y robusta
        $this->validateUploadFile($request);

        try {
            $token = $this->authController->getNewToken();
            $file = $request->file('file');
            
            // ✅ Sanitizar nombre del archivo
            $sanitizedName = $this->sanitizeFileName($file->getClientOriginalName());
            
            // ✅ Validar contenido del archivo (básico)
            if (!$this->isValidPdfFile($file)) {
                return back()->with('error', 'El archivo no parece ser un PDF válido.');
            }

            $response = Http::timeout(30) // ✅ Timeout mayor para uploads
                ->withToken($token)
                ->attach('file', file_get_contents($file->getPathname()), $sanitizedName)
                ->post($this->getApiUrl() . '/upload', [
                    'invitacion_id' => $invitacion->id,
                    'documento_tipo_id' => $request->input('documento_tipo_id'),
                ]);

            if ($response->successful()) {
                Log::info('File uploaded successfully', [
                    'user_id' => Auth::id(),
                    'invitacion_id' => $invitacion->id,
                    'file_name' => $sanitizedName
                ]);
                return back()->with('success', 'Archivo subido exitosamente.');
            }

            // ✅ Log del error sin exponer detalles
            Log::error('File upload failed', [
                'user_id' => Auth::id(),
                'invitacion_id' => $invitacion->id,
                'status' => $response->status()
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

    public function uploadDocumentacionGeneral(Request $request)
    {
        $this->validateUploadFile($request, ['documento_tipo_id' => 'required']);

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
                ->post($this->getApiUrl() . '/uploadDocumentacionGeneral', [
                    'proveedor_id' => $request->input('proveedor_id'),
                    'vencimiento' => $request->input('vencimiento') ?? null,
                    'documento_tipo_id' => $request->input('documento_tipo_id'),
                ]);

            if ($response->successful()) {
                return back()->with('success', 'Documento subido exitosamente.');
            }

            Log::error('General document upload failed', [
                'user_id' => Auth::id(),
                'status' => $response->status()
            ]);

            return back()->with('error', 'Error al procesar el documento. Intente nuevamente.');

        } catch (\Exception $e) {
            Log::error('General document upload exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Error temporal del sistema. Intente nuevamente.');
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

    public function downloadFileFromPlataforma(Request $request)
    {
        $validated = $request->validate([
            'fileName' => 'required|string|max:255',
            'disk' => 'required|string|in:concursos,proveedores' // ✅ Validar discos permitidos
        ]);
        
        try {
            $token = $this->authController->getNewToken();
            
            $response = Http::timeout(30)
                ->withToken($token)
                ->get($this->getApiUrl() . '/download', [
                    'filename' => $validated['fileName'],
                    'disk' => $validated['disk']
                ]);
            
            if ($response->successful()) {
                $tempPath = $validated['disk'] . '/' . $this->sanitizeFileName($validated['fileName']);
                Storage::put($tempPath, $response->body());
                
                return Storage::download($tempPath);
            }
            
            return back()->with('error', 'Error al descargar el archivo.');
            
        } catch (\Exception $e) {
            Log::error('File download exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $validated['fileName'] ?? 'unknown'
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
}