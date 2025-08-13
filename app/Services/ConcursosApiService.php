<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class ConcursosApiService
{
    protected string $apiUrl;
    protected string $token;
    protected string $cuit;

    public function __construct(?string $token = null, ?string $cuit = null)
    {
        $this->apiUrl = rtrim(env('PLATAFORMA_API_URL'), '/');
        $this->token = $token ?? session('jwt_token');
        $this->cuit = $cuit ?? (Auth::user()?->username ?? '');
    }

    /**
     * Obtener listado de concursos del proveedor
     * Según documentación: GET /api/concursos
     */
    public function getConcursos(): ?array
    {
        Log::info('API Request Debug - Concursos', [
            'url' => "{$this->apiUrl}/api/concursos",
            'token' => $this->token,
            'token_length' => strlen($this->token),
            'cuit' => $this->cuit,
        ]);
        
        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/api/concursos");
        
        Log::info('API Response Debug - Concursos', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body_preview' => substr($response->body(), 0, 500),
            'successful' => $response->successful(),
        ]);
        
        if ($response->successful()) {
            $data = $response->json('data') ?? [];
            return $data; // Devolver array directamente
        }
        
        Log::error('API: Error al obtener concursos del proveedor', [
            'status' => $response->status(), 
            'body' => $response->body()
        ]);
        return null;
    }

    /**
     * Obtener concurso específico por ID
     * Según documentación: GET /api/concursos/{concurso_id}
     */
    public function getConcurso(int $concursoId): ?object
    {
        Log::info('API Request Debug - Concurso Específico', [
            'url' => "{$this->apiUrl}/api/concursos/{$concursoId}",
            'token' => $this->token,
            'token_length' => strlen($this->token),
            'cuit' => $this->cuit,
            'concurso_id' => $concursoId,
        ]);
        
        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}");
        
        Log::info('API Response Debug - Concurso Específico', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body_preview' => substr($response->body(), 0, 500),
            'successful' => $response->successful(),
        ]);
        
        if ($response->successful()) {
            return (object) ($response->json('data') ?? []);
        }
        
        Log::error('API: Error al obtener concurso específico', [
            'status' => $response->status(), 
            'body' => $response->body()
        ]);
        return null;
    }

    /**
     * Cambiar intención de participación
     * Según documentación: PATCH /api/concursos/{concurso_id}/invitacion
     */
    public function cambiarIntencion(int $concursoId, int $intencion): bool
    {
        Log::info('API Request Debug - Cambiar Intención', [
            'url' => "{$this->apiUrl}/api/concursos/{$concursoId}/invitacion",
            'intencion' => $intencion,
            'token' => $this->token,
            'token_length' => strlen($this->token),
        ]);
        
        $response = Http::withToken($this->token)
            ->patch("{$this->apiUrl}/api/concursos/{$concursoId}/invitacion", [
                'intencion' => $intencion
            ]);
        
        Log::info('API Response Debug - Cambiar Intención', [
            'status' => $response->status(),
            'body' => $response->body(),
            'successful' => $response->successful(),
        ]);
        
        if ($response->successful()) {
            return true;
        }
        
        Log::error('API: Error al cambiar intención', [
            'status' => $response->status(), 
            'body' => $response->body()
        ]);
        return false;
    }

    /**
     * Obtener tipos de documentos para concursos
     * Según documentación: GET /api/concursos/tipos-documentos
     */
    public function getTiposDocumentosConcursos(): array
    {
        Log::info('API Request Debug - Tipos Documentos', [
            'url' => "{$this->apiUrl}/api/concursos/tipos-documentos",
            'token' => $this->token,
            'token_length' => strlen($this->token),
        ]);
        
        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/api/concursos/tipos-documentos");
        
        Log::info('API Response Debug - Tipos Documentos', [
            'status' => $response->status(),
            'body_preview' => substr($response->body(), 0, 500),
            'successful' => $response->successful(),
        ]);
        
        if ($response->successful()) {
            return $response->json('data') ?? [];
        }
        
        Log::error('API: Error al obtener tipos de documentos de concursos', [
            'status' => $response->status(), 
            'body' => $response->body()
        ]);
        return [];
    }

    /**
     * Subir documento de concurso
     * Según documentación: POST /api/concursos/{concurso_id}/documentos
     */
    public function subirDocumentoConcurso(int $concursoId, UploadedFile $file, int $documentoTipoId): ?object
    {
        Log::info('API Request Debug - Subir Documento Concurso', [
            'url' => "{$this->apiUrl}/api/concursos/{$concursoId}/documentos",
            'documento_tipo_id' => $documentoTipoId,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'token' => $this->token,
            'token_length' => strlen($this->token),
        ]);

        $response = Http::withToken($this->token)
            ->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
            ->post("{$this->apiUrl}/api/concursos/{$concursoId}/documentos", [
                'documento_tipo_id' => $documentoTipoId,
            ]);

        Log::info('API Response Debug - Subir Documento Concurso', [
            'status' => $response->status(),
            'body' => $response->body(),
            'successful' => $response->successful(),
        ]);

        if ($response->successful()) {
            $data = $response->json('data') ?? [];
            return (object) $data;
        }
        
        Log::error('API: Error al subir documento de concurso', [
            'status' => $response->status(), 
            'body' => $response->body()
        ]);
        return null;
    }

    /**
     * Subir documento adicional
     * Según documentación: POST /api/concursos/{concurso_id}/documentos
     */
    public function subirDocumentoAdicional(int $concursoId, UploadedFile $file, ?string $comentarios = null): ?object
    {
        Log::info('API Request Debug - Subir Documento Adicional', [
            'url' => "{$this->apiUrl}/api/concursos/{$concursoId}/documentos",
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'comentarios' => $comentarios,
            'token' => $this->token,
            'token_length' => strlen($this->token),
        ]);

        $data = [];
        if ($comentarios) {
            $data['comentarios'] = $comentarios;
        }

        $response = Http::withToken($this->token)
            ->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
            ->post("{$this->apiUrl}/api/concursos/{$concursoId}/documentos", $data);

        Log::info('API Response Debug - Subir Documento Adicional', [
            'status' => $response->status(),
            'body' => $response->body(),
            'successful' => $response->successful(),
        ]);

        if ($response->successful()) {
            $data = $response->json('data') ?? [];
            return (object) $data;
        }
        
        Log::error('API: Error al subir documento adicional', [
            'status' => $response->status(), 
            'body' => $response->body()
        ]);
        return null;
    }

    /**
     * Obtener documentos de la invitación
     * Según documentación: GET /api/concursos/{concurso_id}/documentos
     */
    public function getDocumentosInvitacion(int $concursoId): array
    {
        Log::info('API Request Debug - Documentos Invitación', [
            'url' => "{$this->apiUrl}/api/concursos/{$concursoId}/documentos",
            'token' => $this->token,
            'token_length' => strlen($this->token),
        ]);
        
        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}/documentos");
        
        Log::info('API Response Debug - Documentos Invitación', [
            'status' => $response->status(),
            'body_preview' => substr($response->body(), 0, 500),
            'successful' => $response->successful(),
        ]);
        
        if ($response->successful()) {
            return $response->json('data') ?? [];
        }
        
        Log::error('API: Error al obtener documentos de la invitación', [
            'status' => $response->status(), 
            'body' => $response->body()
        ]);
        return [];
    }

    /**
     * Descargar documento de concurso
     * Según documentación: GET /api/concursos/{concurso_id}/documentos/{documento_id}/descargar
     */
    public function descargarDocumentoConcurso(int $concursoId, int $documentoId)
    {
        $url = "{$this->apiUrl}/api/concursos/{$concursoId}/documentos/{$documentoId}/descargar";
        
        Log::info('API Request Debug - Descargar Documento', [
            'url' => $url,
            'token' => $this->token,
            'token_length' => strlen($this->token),
        ]);
        
        return Http::withToken($this->token)->get($url);
    }

    /**
     * Descargar documento de concurso y manejar la respuesta
     */
    public function descargarDocumentoConcursoResponse(int $concursoId, int $documentoId)
    {
        $response = $this->descargarDocumentoConcurso($concursoId, $documentoId);
        
        Log::info('API Response Debug - Descargar Documento', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'successful' => $response->successful(),
        ]);
        
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
     * Verificar documento de proveedor
     * Según documentación: GET /api/concursos/{concurso_id}/documentos/{documento_tipo_id}/verificar
     */
    public function verificarDocumentoProveedor(int $concursoId, int $documentoTipoId): ?object
    {
        Log::info('API Request Debug - Verificar Documento Proveedor', [
            'url' => "{$this->apiUrl}/api/concursos/{$concursoId}/documentos/{$documentoTipoId}/verificar",
            'token' => $this->token,
            'token_length' => strlen($this->token),
        ]);
        
        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}/documentos/{$documentoTipoId}/verificar");
        
        Log::info('API Response Debug - Verificar Documento Proveedor', [
            'status' => $response->status(),
            'body' => $response->body(),
            'successful' => $response->successful(),
        ]);
        
        if ($response->successful()) {
            $data = $response->json('data') ?? [];
            return (object) $data;
        }
        
        Log::error('API: Error al verificar documento de proveedor', [
            'status' => $response->status(), 
            'body' => $response->body()
        ]);
        return null;
    }

    /**
     * Obtener tipos de documentos de oferta para un concurso específico
     * Según documentación: GET /api/concursos/{concurso_id}/tipos-documentos-oferta
     * 
     * Estructura de respuesta:
     * - id: ID del tipo de documento
     * - nombre: Nombre del tipo de documento
     * - descripcion: Descripción del tipo
     * - de_concurso: 0 (documentos de oferta)
     * - obligatorio: 0/1 si es obligatorio
     * - tipo_documento_proveedor_id: ID del tipo de documento del proveedor (si aplica)
     * - tipo_documento_proveedor: Objeto con datos del tipo de documento del proveedor
     * - documentos_oferta: Array de documentos ya cargados
     * - proveedor_id: ID del proveedor
     * - concurso_id: ID del concurso
     */
    public function getTiposDocumentosOferta(int $concursoId): ?array
    {
        Log::info('API Request Debug - Tipos Documentos Oferta', [
            'url' => "{$this->apiUrl}/api/concursos/{$concursoId}/tipos-documentos-oferta",
            'token' => $this->token,
            'token_length' => strlen($this->token),
            'concurso_id' => $concursoId,
        ]);
        
        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}/tipos-documentos-oferta");
        
        Log::info('API Response Debug - Tipos Documentos Oferta', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body_preview' => substr($response->body(), 0, 500),
            'successful' => $response->successful(),
        ]);
        
        if ($response->successful()) {
            $data = $response->json('data') ?? [];
            return $data;
        }
        
        Log::error('API: Error al obtener tipos de documentos de oferta', [
            'status' => $response->status(), 
            'body' => $response->body()
        ]);
        return null;
    }

    /**
     * Eliminar documento de oferta
     * Según documentación: DELETE /api/concursos/{concurso_id}/documentos/{documento_id}
     */
    public function eliminarDocumento(int $concursoId, int $documentoId): bool
    {
        Log::info('API Request Debug - Eliminar Documento', [
            'url' => "{$this->apiUrl}/api/concursos/{$concursoId}/documentos/{$documentoId}",
            'token' => $this->token,
            'token_length' => strlen($this->token),
            'concurso_id' => $concursoId,
            'documento_id' => $documentoId,
        ]);
        
        $response = Http::withToken($this->token)
            ->delete("{$this->apiUrl}/api/concursos/{$concursoId}/documentos/{$documentoId}");
        
        Log::info('API Response Debug - Eliminar Documento', [
            'status' => $response->status(),
            'body' => $response->body(),
            'successful' => $response->successful(),
        ]);
        
        if ($response->successful()) {
            Log::info('Documento eliminado exitosamente via API', [
                'concurso_id' => $concursoId,
                'documento_id' => $documentoId,
                'response' => $response->json()
            ]);
            return true;
        }
        
        Log::error('API: Error al eliminar documento', [
            'status' => $response->status(), 
            'body' => $response->body(),
            'concurso_id' => $concursoId,
            'documento_id' => $documentoId
        ]);
        return false;
    }

    /**
     * Dar de baja oferta completa
     * Según documentación: DELETE /api/concursos/{concurso_id}/oferta
     */
    public function darBajaOferta(int $concursoId): bool
    {
        Log::info('API Request Debug - Dar Baja Oferta', [
            'url' => "{$this->apiUrl}/api/concursos/{$concursoId}/oferta",
            'token' => $this->token,
            'token_length' => strlen($this->token),
            'concurso_id' => $concursoId,
        ]);
        
        $response = Http::withToken($this->token)
            ->delete("{$this->apiUrl}/api/concursos/{$concursoId}/oferta");
        
        Log::info('API Response Debug - Dar Baja Oferta', [
            'status' => $response->status(),
            'body' => $response->body(),
            'successful' => $response->successful(),
        ]);
        
        if ($response->successful()) {
            Log::info('Oferta dada de baja exitosamente via API', [
                'concurso_id' => $concursoId,
                'response' => $response->json()
            ]);
            return true;
        }
        
        Log::error('API: Error al dar de baja oferta', [
            'status' => $response->status(), 
            'body' => $response->body(),
            'concurso_id' => $concursoId
        ]);
        return false;
    }

    /**
     * Obtener documentos adicionales
     * Según documentación: GET /api/concursos/{concurso_id}/documentos-adicionales
     */
    public function getDocumentosAdicionales(int $concursoId): array
    {
        Log::info('API Request Debug - Documentos Adicionales', [
            'url' => "{$this->apiUrl}/api/concursos/{$concursoId}/documentos-adicionales",
            'token' => $this->token,
            'token_length' => strlen($this->token),
            'concurso_id' => $concursoId,
        ]);
        
        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}/documentos-adicionales");
        
        Log::info('API Response Debug - Documentos Adicionales', [
            'status' => $response->status(),
            'body_preview' => substr($response->body(), 0, 500),
            'successful' => $response->successful(),
        ]);
        
        if ($response->successful()) {
            return $response->json('data') ?? [];
        }
        
        Log::error('API: Error al obtener documentos adicionales', [
            'status' => $response->status(), 
            'body' => $response->body()
        ]);
        return [];
    }
} 