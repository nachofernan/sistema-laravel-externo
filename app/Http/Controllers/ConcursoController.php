<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ConcursosApiService;

class ConcursoController extends Controller
{
    protected $authController;

    public function __construct(AuthController $authController)
    {
        $this->authController = $authController;
    }

    /**
     * Listado de concursos del proveedor - AHORA CON API
     */
    public function index()
    {
        try {
            // Test de conversión recursiva
            $this->testArrayToObjectRecursive();
            
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            $concursosData = $api->getConcursos();
    
            // Verificar si hubo un error real en la API (null) vs array vacío (sin concursos)
            if ($concursosData === null) {
                Log::error('Concursos Index: No se pudo obtener datos de concursos', [
                    'user_id' => $user->id,
                    'cuit' => $user->username,
                    'jwt_token' => $token,
                    'session_id' => session()->getId(),
                    'user' => $user,
                ]);
                
                // ✅ Cambio clave: Especificar el guard 'web'
                Auth::guard('web')->logout();
                session()->invalidate();
                session()->regenerateToken();
                return redirect()->route('login')
                    ->with('error', 'Error al cargar datos de concursos')->withCookie(cookie()->forget('laravel_session'));
            }

            // Debug: Log de los datos recibidos
            Log::info('Concursos Data Debug', [
                'data_type' => gettype($concursosData),
                'data_count' => is_array($concursosData) ? count($concursosData) : 'not array',
                'is_array' => is_array($concursosData),
                'is_object' => is_object($concursosData),
                'raw_data' => $concursosData, // Log completo de los datos
            ]);

            // Usar el patrón del dashboard: crear colecciones a medida que se necesiten
            $concursos = is_array($concursosData) ? $concursosData : [];


            $invitaciones_activas = [];
            $invitaciones_finalizadas = [];
            foreach ($concursos as $concurso) {
                $concurso = is_array($concurso) ? (object) $concurso : $concurso;
                if(isset($concurso->estado['estado_actual']) && 
                   ($concurso->estado['estado_actual'] == 'activo')){
                    $invitaciones_activas[] = $concurso;
                }else{
                    $invitaciones_finalizadas[] = $concurso;
                }
            }
    
            return view('concursos.index', compact('invitaciones_activas', 'invitaciones_finalizadas'));
        } catch (\Exception $e) {
            Log::error('Concursos Index exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => session()->getId(),
            ]);
            
            // ✅ Cambio clave: Especificar el guard 'web'
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Error temporal del sistema. Intente nuevamente.')->withCookie(cookie()->forget('laravel_session'));
        }
    }

    /**
     * Mostrar concurso específico - AHORA CON API
     */
    public function show(int $concursoId)
    {
        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            $concursoData = $api->getConcurso($concursoId);
    
            if (!$concursoData) {
                Log::error('Concurso Show: No se pudo obtener datos del concurso', [
                    'user_id' => $user->id,
                    'cuit' => $user->username,
                    'concurso_id' => $concursoId,
                    'jwt_token' => $token,
                    'session_id' => session()->getId(),
                    'user' => $user,
                ]);
                return redirect()->route('concursos.index')
                    ->with('error', 'Error al cargar datos del concurso.');
            }

            // Usar el patrón del dashboard: pasar los datos tal como vienen
            $concurso = $concursoData;

            // Cargar documentos de oferta al inicio si hay invitación
            if (isset($concurso->invitacion) && $concurso->invitacion) {
                $tiposDocumentosOferta = $api->getTiposDocumentosOferta($concursoId);
                if ($tiposDocumentosOferta) {
                    // Agregar los documentos de oferta al objeto concurso
                    $concurso->tipos_documentos_oferta = $tiposDocumentosOferta;
                }
                
                // Cargar documentos adicionales
                $documentosAdicionales = $api->getDocumentosAdicionales($concursoId);
                if ($documentosAdicionales) {
                    // Agregar los documentos adicionales al objeto concurso
                    $concurso->documentos_adicionales = $documentosAdicionales;
                }
            }
    
            return view('concursos.show', compact('concurso'));
        } catch (\Exception $e) {
            Log::error('Concurso Show exception', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => session()->getId(),
            ]);
            return redirect()->route('concursos.index')
                ->with('error', 'Error temporal del sistema. Intente nuevamente.');
        }
    }

    /**
     * Cambiar intención de participación
     */
    public function cambiarIntencion(Request $request, int $concursoId)
    {
        try {
            $validated = $request->validate([
                'intencion' => 'required|integer|in:0,1,2,3'
            ]);

            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            
            $success = $api->cambiarIntencion($concursoId, $validated['intencion']);

            if ($success) {
                return redirect()->route('concursos.show', $concursoId)
                    ->with('success', 'Intención actualizada correctamente.');
            } else {
                return redirect()->route('concursos.show', $concursoId)
                    ->with('error', 'Error al actualizar la intención.');
            }
        } catch (\Exception $e) {
            Log::error('Cambiar Intención exception', [
                'user_id' => Auth::id(),
                'concurso_id' => $concursoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('concursos.show', $concursoId)
                ->with('error', 'Error temporal del sistema. Intente nuevamente.');
        }
    }

    /**
     * ✅ Método de prueba para verificar conversión recursiva
     */
    private function testArrayToObjectRecursive()
    {
        $testData = [
            'id' => 1,
            'nombre' => 'Test Concurso',
            'estado' => [
                'id' => 2,
                'estado_actual' => 'activo'
            ],
            'contactos' => [
                [
                    'id' => 1,
                    'nombre' => 'Contacto 1',
                    'tipo' => 'administrativo'
                ],
                [
                    'id' => 2,
                    'nombre' => 'Contacto 2',
                    'tipo' => 'tecnico'
                ]
            ],
            'documentos_requeridos' => [
                [
                    'id' => 1,
                    'nombre' => 'Documento 1',
                    'obligatorio' => true
                ]
            ]
        ];

        $result = $this->convertToObjectsRecursive($testData);
        
        Log::info('Test ArrayToObjectRecursive', [
            'original' => $testData,
            'converted' => $result,
            'estado_is_object' => is_object($result->estado),
            'contactos_is_array' => is_array($result->contactos),
            'contactos_first_is_object' => is_object($result->contactos[0]),
            'documentos_requeridos_is_array' => is_array($result->documentos_requeridos),
            'documentos_requeridos_first_is_object' => is_object($result->documentos_requeridos[0]),
        ]);

        return $result;
    }

    /**
     * ✅ Método de debug para inspeccionar estructura de datos
     */
    private function debugDataStructure($data, $level = 0)
    {
        $indent = str_repeat('  ', $level);
        
        if (is_array($data)) {
            echo $indent . "ARRAY (" . count($data) . " items):\n";
            foreach ($data as $key => $value) {
                echo $indent . "  [$key] => ";
                if (is_array($value) || is_object($value)) {
                    echo "\n";
                    $this->debugDataStructure($value, $level + 2);
                } else {
                    echo $value . "\n";
                }
            }
        } elseif (is_object($data)) {
            echo $indent . "OBJECT (" . get_class($data) . "):\n";
            foreach ($data as $key => $value) {
                echo $indent . "  $key => ";
                if (is_array($value) || is_object($value)) {
                    echo "\n";
                    $this->debugDataStructure($value, $level + 2);
                } else {
                    echo $value . "\n";
                }
            }
        } else {
            echo $indent . "VALUE: " . $data . "\n";
        }
    }

    /**
     * ✅ Convertir arrays a objetos de manera recursiva manteniendo estructura
     */
    private function arrayToObjectRecursive($data)
    {
        // Si no es array, devolver tal como está
        if (!is_array($data)) {
            return $data;
        }

        // Si es un array indexado (lista), convertir cada elemento recursivamente
        if (array_keys($data) === range(0, count($data) - 1)) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $this->arrayToObjectRecursive($item);
            }
            return $result;
        }

        // Si es un array asociativo, convertir a objeto
        $object = new \stdClass();
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Si el valor es un array, aplicar recursivamente
                $object->$key = $this->arrayToObjectRecursive($value);
            } else {
                $object->$key = $value;
            }
        }
        
        return $object;
    }

    /**
     * ✅ Método más robusto para convertir completamente a objetos
     */
    private function convertToObjectsRecursive($data)
    {
        // Si ya es un objeto stdClass, devolver tal como está
        if (is_object($data) && get_class($data) === 'stdClass') {
            return $data;
        }

        // Si es un array, convertir a objeto
        if (is_array($data)) {
            return $this->arrayToObjectRecursive($data);
        }

        // Si es otro tipo de objeto, convertirlo a array y luego a objeto
        if (is_object($data)) {
            $array = json_decode(json_encode($data), true);
            return $this->arrayToObjectRecursive($array);
        }

        return $data;
    }

    /**
     * ✅ Método centralizado para URL de API (mismo patrón que AuthController)
     */
    private function getApiUrl(): string
    {
        $url = env('PLATAFORMA_API_URL');
        
        if (empty($url)) {
            Log::critical('PLATAFORMA_API_URL not configured');
            throw new \Exception('Configuración del sistema incompleta');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::critical('Invalid PLATAFORMA_API_URL format', ['url' => $url]);
            throw new \Exception('Configuración del sistema inválida');
        }

        return rtrim($url, '/');
    }
}