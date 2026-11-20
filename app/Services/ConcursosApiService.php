<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConcursosApiService
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
     * Envía un pedido autenticado a la API interna. Si la API responde 401 (token vencido),
     * renueva el JWT y reintenta UNA sola vez. Si la renovación falla, devuelve el 401 original.
     *
     * El 401 lo devuelve la capa de autenticación de la API antes de ejecutar la acción, así que
     * reintentar una escritura no la duplica. El pedido se arma dentro del closure para que un
     * reintento vuelva a construirlo completo (incluidos los adjuntos).
     *
     * @param  callable(PendingRequest): Response  $armarPedido
     */
    private function enviar(callable $armarPedido): Response
    {
        $tokenUsado = $this->token ?? '';
        $response = $armarPedido(Http::withToken($tokenUsado));

        if ($response->status() !== 401) {
            return $response;
        }

        // Otra instancia del servicio pudo haber renovado el token en este mismo request:
        // si la sesión ya tiene uno distinto, se reintenta con ese sin pedir otro.
        $tokenEnSesion = session('jwt_token');
        if ($tokenEnSesion && $tokenEnSesion !== $tokenUsado) {
            $this->token = $tokenEnSesion;
        } elseif (! $this->renovarToken()) {
            return $response;
        }

        Log::info('API: token vencido, reintentando con token renovado', ['user_id' => Auth::id()]);

        return $armarPedido(Http::withToken($this->token ?? ''));
    }

    /**
     * Pide un JWT nuevo al sistema interno y lo guarda en la propiedad y en la sesión.
     */
    private function renovarToken(): bool
    {
        try {
            $nuevoToken = app(\App\Http\Controllers\AuthController::class)->getNewToken();
            $this->token = $nuevoToken;
            session(['jwt_token' => $nuevoToken]);

            return true;
        } catch (\Exception $e) {
            Log::error('API: no se pudo renovar el token', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Registra un error de la API con el usuario y el contexto que permitan reconstruir un incidente.
     */
    private function registrarError(string $mensaje, Response $response, array $contexto = []): void
    {
        Log::error($mensaje, [
            'user_id' => Auth::id(),
            ...$contexto,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    }

    /**
     * Obtener listado de concursos del proveedor
     * Según documentación: GET /api/concursos
     */
    public function getConcursos(): ?array
    {
        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->get("{$this->apiUrl}/api/concursos"));

        if ($response->successful()) {
            return $response->json('data') ?? [];
        }

        $this->registrarError('API: Error al obtener concursos del proveedor', $response);

        return null;
    }

    /**
     * Obtener concurso específico por ID
     * Según documentación: GET /api/concursos/{concurso_id}
     */
    public function getConcurso(int $concursoId): ?object
    {
        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}"));

        if ($response->successful()) {
            return (object) ($response->json('data') ?? []);
        }

        $this->registrarError('API: Error al obtener concurso específico', $response, [
            'concurso_id' => $concursoId,
        ]);

        return null;
    }

    /**
     * Cambiar intención de participación
     * Según documentación: PATCH /api/concursos/{concurso_id}/invitacion
     */
    public function cambiarIntencion(int $concursoId, int $intencion, ?string $observaciones = null): bool
    {
        $payload = ['intencion' => $intencion];
        if ($intencion === 2 && ! empty($observaciones)) {
            $payload['observaciones'] = $observaciones;
        }

        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->patch("{$this->apiUrl}/api/concursos/{$concursoId}/invitacion", $payload));

        if ($response->successful()) {
            Log::info('Intención de participación cambiada', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId,
                'intencion' => $intencion,
            ]);

            return true;
        }

        $this->registrarError('API: Error al cambiar intención', $response, [
            'concurso_id' => $concursoId,
            'intencion' => $intencion,
        ]);

        return false;
    }

    /**
     * Obtener tipos de documentos para concursos
     * Según documentación: GET /api/concursos/tipos-documentos
     */
    public function getTiposDocumentosConcursos(): array
    {
        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->get("{$this->apiUrl}/api/concursos/tipos-documentos"));

        if ($response->successful()) {
            return $response->json('data') ?? [];
        }

        $this->registrarError('API: Error al obtener tipos de documentos de concursos', $response);

        return [];
    }

    /**
     * Subir documento de concurso
     * Según documentación: POST /api/concursos/{concurso_id}/documentos
     */
    public function subirDocumentoConcurso(int $concursoId, UploadedFile $file, ?int $documentoTipoId = null): ?object
    {
        $payload = [];
        // Solo agregamos el ID si no es nulo (para que no mande "documento_tipo_id": null a la API si no lo espera)
        if ($documentoTipoId) {
            $payload['documento_tipo_id'] = $documentoTipoId;
        }

        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
            ->post("{$this->apiUrl}/api/concursos/{$concursoId}/documentos", $payload));

        if ($response->successful()) {
            Log::info('Documento de concurso subido', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId,
                'documento_tipo_id' => $documentoTipoId,
            ]);

            return (object) ($response->json('data') ?? []);
        }

        $this->registrarError('API: Error al subir documento de concurso', $response, [
            'concurso_id' => $concursoId,
            'documento_tipo_id' => $documentoTipoId,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);

        return null;
    }

    /**
     * Subir documento adicional
     * Según documentación: POST /api/concursos/{concurso_id}/documentos
     */
    public function subirDocumentoAdicional(int $concursoId, UploadedFile $file, ?string $comentarios = null): ?object
    {
        $payload = [];
        if ($comentarios) {
            $payload['comentarios'] = $comentarios;
        }

        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
            ->post("{$this->apiUrl}/api/concursos/{$concursoId}/documentos", $payload));

        if ($response->successful()) {
            Log::info('Documento adicional subido', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId,
            ]);

            return (object) ($response->json('data') ?? []);
        }

        $this->registrarError('API: Error al subir documento adicional', $response, [
            'concurso_id' => $concursoId,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);

        return null;
    }

    /**
     * Obtener documentos de la invitación
     * Según documentación: GET /api/concursos/{concurso_id}/documentos
     */
    public function getDocumentosInvitacion(int $concursoId): array
    {
        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}/documentos"));

        if ($response->successful()) {
            return $response->json('data') ?? [];
        }

        $this->registrarError('API: Error al obtener documentos de la invitación', $response, [
            'concurso_id' => $concursoId,
        ]);

        return [];
    }

    /**
     * Descargar documento de concurso
     * Según documentación: GET /api/concursos/{concurso_id}/documentos/{documento_id}/descargar
     */
    public function descargarDocumentoConcurso(int $concursoId, int $documentoId): Response
    {
        return $this->enviar(fn (PendingRequest $http) => $http
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}/documentos/{$documentoId}/descargar"));
    }

    /**
     * Descargar documento de concurso y manejar la respuesta
     */
    public function descargarDocumentoConcursoResponse(int $concursoId, int $documentoId)
    {
        $response = $this->descargarDocumentoConcurso($concursoId, $documentoId);

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
            'status' => $status,
        ], $status);
    }

    /**
     * Verificar documento de proveedor
     * Según documentación: GET /api/concursos/{concurso_id}/documentos/{documento_tipo_id}/verificar
     */
    public function verificarDocumentoProveedor(int $concursoId, int $documentoTipoId): ?object
    {
        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}/documentos/{$documentoTipoId}/verificar"));

        if ($response->successful()) {
            return (object) ($response->json('data') ?? []);
        }

        $this->registrarError('API: Error al verificar documento de proveedor', $response, [
            'concurso_id' => $concursoId,
            'documento_tipo_id' => $documentoTipoId,
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
        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}/tipos-documentos-oferta"));

        if ($response->successful()) {
            return $response->json('data') ?? [];
        }

        $this->registrarError('API: Error al obtener tipos de documentos de oferta', $response, [
            'concurso_id' => $concursoId,
        ]);

        return null;
    }

    /**
     * Eliminar documento de oferta
     * Según documentación: DELETE /api/concursos/{concurso_id}/documentos/{documento_id}
     */
    public function eliminarDocumento(int $concursoId, int $documentoId): bool
    {
        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->delete("{$this->apiUrl}/api/concursos/{$concursoId}/documentos/{$documentoId}"));

        if ($response->successful()) {
            Log::info('Documento eliminado exitosamente via API', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId,
                'documento_id' => $documentoId,
            ]);

            return true;
        }

        $this->registrarError('API: Error al eliminar documento', $response, [
            'concurso_id' => $concursoId,
            'documento_id' => $documentoId,
        ]);

        return false;
    }

    /**
     * Dar de baja oferta completa
     * Según documentación: DELETE /api/concursos/{concurso_id}/oferta
     */
    public function darBajaOferta(int $concursoId): bool
    {
        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->delete("{$this->apiUrl}/api/concursos/{$concursoId}/oferta"));

        if ($response->successful()) {
            Log::info('Oferta dada de baja exitosamente via API', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId,
            ]);

            return true;
        }

        $this->registrarError('API: Error al dar de baja oferta', $response, [
            'concurso_id' => $concursoId,
        ]);

        return false;
    }

    /**
     * Obtener documentos adicionales
     * Según documentación: GET /api/concursos/{concurso_id}/documentos-adicionales
     */
    public function getDocumentosAdicionales(int $concursoId): array
    {
        $response = $this->enviar(fn (PendingRequest $http) => $http
            ->get("{$this->apiUrl}/api/concursos/{$concursoId}/documentos-adicionales"));

        if ($response->successful()) {
            return $response->json('data') ?? [];
        }

        $this->registrarError('API: Error al obtener documentos adicionales', $response, [
            'concurso_id' => $concursoId,
        ]);

        return [];
    }
}
