<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class ProveedorApiService
{
    protected string $apiUrl;
    protected ?string $token;
    protected string $cuit;

    public function __construct(?string $token = null, ?string $cuit = null)
    {
        $this->apiUrl = rtrim(env('PLATAFORMA_API_URL'), '/');
        $this->token = $token ?? session('jwt_token');
        $this->cuit = $cuit ?? (Auth::user()?->username ?? '');
    }

    /**
     * Obtener datos completos del proveedor
     */
    public function getProveedor(): ?object
    {
        /* Log::info('API Request Debug', [
            'url' => "{$this->apiUrl}/api/proveedores/{$this->cuit}",
            'token' => $this->token,
            'token_length' => strlen($this->token ?? ''),
            'cuit' => $this->cuit,
        ]); */
        
        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/api/proveedores/{$this->cuit}");

        /* Log::info('API Response Debug', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body_preview' => substr($response->body(), 0, 500),
            'successful' => $response->successful(),
        ]); */


// Si el token expir� (401), intentamos renovarlo una vez
    if ($response->status() === 401) {
        Log::info('Token expirado detectado. Intentando renovar...');
        
        if ($this->refreshToken()) {
            // Reintentamos la petici�n con el nuevo token
            $response = Http::withToken($this->token)->get("{$this->apiUrl}/api/proveedores/{$this->cuit}");
        }
    }
        
        /* Log::info('API Response Debug', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body_preview' => substr($response->body(), 0, 500),
            'successful' => $response->successful(),
        ]); */
        
        if ($response->successful()) {
            return (object) ($response->json('data') ?? []);
        }
        Log::error('API: Error al obtener datos del proveedor', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

/**
 * M�todo privado para obtener un nuevo token y actualizar la sesi�n
 */
private function refreshToken(): bool
{
    try {
        // Llamamos a tu AuthController para generar uno nuevo
        $newToken = app(\App\Http\Controllers\AuthController::class)->getNewToken();
        
        // Actualizamos la propiedad de la clase y la sesi�n
        $this->token = $newToken;
        session(['jwt_token' => $newToken]);
        
        return true;
    } catch (\Exception $e) {
        Log::error('No se pudo refrescar el token', ['error' => $e->getMessage()]);
        return false;
    }
}

    /**
     * Obtener tipos de documentos y apoderados
     */
    public function getTiposDocumentos(): array
    {
        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/api/proveedores/tipos-documentos");
        if ($response->successful()) {
            return $response->json('data') ?? [];
        }
        Log::error('API: Error al obtener tipos de documentos', ['status' => $response->status(), 'body' => $response->body()]);
        return [];
    }

    /**
     * Obtener tipos de rubros y subrubros
     */
    public function getRubros(): array
    {
        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/api/proveedores/tipos-rubros");
        if ($response->successful()) {
            return $response->json('data') ?? [];
        }
        Log::error('API: Error al obtener rubros', ['status' => $response->status(), 'body' => $response->body()]);
        return [];
    }

    /**
     * Actualizar subrubros del proveedor
     */
    public function setSubrubros(array $subrubroIds): bool
    {
        $response = Http::withToken($this->token)
            ->post("{$this->apiUrl}/api/proveedores/{$this->cuit}/subrubros", [
                'subrubro_ids' => $subrubroIds,
            ]);
        if ($response->successful()) {
            return true;
        }
        Log::error('API: Error al actualizar subrubros', ['status' => $response->status(), 'body' => $response->body()]);
        return false;
    }

    /**
     * Subir documento de proveedor
     */
    public function subirDocumento(UploadedFile $file, int $documentoTipoId, ?string $vencimiento = null): ?object
    {
        $multipart = [
            [
                'name'     => 'file',
                'contents' => fopen($file->getPathname(), 'r'),
                'filename' => $file->getClientOriginalName(),
            ],
            [
                'name'     => 'documento_tipo_id',
                'contents' => $documentoTipoId,
            ],
        ];
        if ($vencimiento) {
            $multipart[] = [
                'name'     => 'vencimiento',
                'contents' => $vencimiento,
            ];
        }
        $response = Http::withToken($this->token)
            ->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
            ->post("{$this->apiUrl}/api/proveedores/{$this->cuit}/documentos", [
                'documento_tipo_id' => $documentoTipoId,
                'vencimiento' => $vencimiento,
            ]);
        if ($response->successful()) {
            return (object) ($response->json('data') ?? []);
        }
        Log::error('API: Error al subir documento', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    /**
     * Descargar documento de proveedor (devuelve el response para streaming)
     */
    public function descargarDocumento(int $documentoId)
    {
        $url = "{$this->apiUrl}/api/proveedores/{$this->cuit}/documentos/{$documentoId}/descargar";
        return Http::withToken($this->token)->get($url);
    }

    /**
     * Descargar documento y manejar la respuesta
     */
    public function descargarDocumentoResponse(int $documentoId)
    {
        $response = $this->descargarDocumento($documentoId);
        if ($response->successful()) {
            // Obtener el nombre del archivo del header Content-Disposition
            $contentDisposition = $response->header('Content-Disposition');
            $filename = 'documento.pdf'; // Default
            
            if ($contentDisposition) {
                if (preg_match('/filename="([^"]+)"/', $contentDisposition, $matches)) {
                    $filename = $matches[1];
                } elseif (preg_match('/filename=([^;]+)/', $contentDisposition, $matches)) {
                    $filename = $matches[1];
                }
            }
            
            return response($response->body())
                ->header('Content-Type', $response->header('Content-Type', 'application/octet-stream'))
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                ->header('Content-Length', strlen($response->body()));
        }
        
        // Si hay error, devolver respuesta de error
        $errorData = $response->json();
        $message = $errorData['message'] ?? 'Error al descargar el documento';
        $status = $response->status();
        
        return response()->json([
            'success' => false,
            'message' => $message,
            'status' => $status
        ], $status);
    }

    /**
     * Subir apoderado
     */
    public function subirApoderado(UploadedFile $file, string $tipo, ?string $nombre = null, ?string $vencimiento = null): ?object
    {
        $response = Http::withToken($this->token)
            ->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
            ->post("{$this->apiUrl}/api/proveedores/{$this->cuit}/apoderados", [
                'tipo' => $tipo,
                'nombre' => $nombre,
                'vencimiento' => $vencimiento,
            ]);

        if ($response->successful()) {
            return (object) ($response->json('data') ?? []);
        }
        
        Log::error('API: Error al subir apoderado', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }
} 