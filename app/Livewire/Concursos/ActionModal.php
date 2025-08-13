<?php

namespace App\Livewire\Concursos;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Services\ConcursosApiService;

class ActionModal extends Component
{
    public $open = false;
    public $concurso;
    public $invitacion;
    public $documentacion_completa;
    public $concurso_activo;
    public $antes_del_cierre;
    public $obligatorios_completos;
    
    // Propiedades para dar de baja la oferta
    public $dando_baja = false;
    public $password = '';

    public function mount($concurso, $invitacion) {
        $this->concurso = $concurso;
        $this->invitacion = $invitacion;
        $this->initializeState();
    }

    private function initializeState()
    {
        // Verificar si el concurso está activo y antes del cierre
        $this->concurso_activo = isset($this->concurso->estado) && 
            (is_array($this->concurso->estado) ? $this->concurso->estado['estado_actual'] : $this->concurso->estado->estado_actual) == 'activo';
        
        $this->antes_del_cierre = isset($this->concurso->fecha_cierre) && 
            Carbon::parse($this->concurso->fecha_cierre)->isFuture();
        
        // Verificar documentación
        $this->checkDocumentacion();
    }

    public function checkDocumentacion() {
        $this->documentacion_completa = true;
        $this->obligatorios_completos = true;
        try {
            $this->checkDocumentacionFromApi();
            // Debug log
            Log::info('Documentación check result', [
                'concurso_id' => $this->concurso->id ?? 'unknown',
                'completa' => $this->documentacion_completa,
                'intencion' => $this->invitacion->intencion ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking documentation', [
                'error' => $e->getMessage(),
                'concurso_id' => $this->concurso->id ?? 'unknown'
            ]);
            $this->documentacion_completa = false;
            $this->obligatorios_completos = false;
        }
    }

    /**
     * Verificar documentación basándose en los tipos de documentos de oferta
     */
    private function checkDocumentacionFromApi()
    {
        // Obtener los tipos de documentos de oferta
        $tiposDocumentosOferta = $this->getTiposDocumentosOferta();
        
        foreach($tiposDocumentosOferta as $tipoDocumento) {
            // Verificar si tiene documentos subidos
            $tieneDocumentos = isset($tipoDocumento['documentos_oferta']) && 
                              is_array($tipoDocumento['documentos_oferta']) && 
                              count($tipoDocumento['documentos_oferta']) > 0;
            
            if (!$tieneDocumentos) {
                // Si no tiene documentos subidos, verificar si tiene documento de proveedor asociado
                if ($tipoDocumento['tipo_documento_proveedor_id'] 
                    && ($tipoDocumento['tipo_documento_proveedor']['fecha_vencimiento'] 
                    && Carbon::parse($tipoDocumento['tipo_documento_proveedor']['fecha_vencimiento'])->greaterThan(Carbon::parse($this->concurso->fecha_cierre)))
                    || !$tipoDocumento['tipo_documento_proveedor']['fecha_vencimiento']) {
                        continue;
                }
                $this->documentacion_completa = false;
                if ($tipoDocumento['obligatorio']) {
                    $this->obligatorios_completos = false;
                }
            }
        }
    }

    /**
     * Obtener tipos de documentos de oferta
     */
    private function getTiposDocumentosOferta()
    {
        // Si ya están cargados en el concurso, usarlos
        if (isset($this->concurso->tipos_documentos_oferta)) {
            return $this->concurso->tipos_documentos_oferta;
        }

        // Si no, cargarlos desde la API
        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new \App\Services\ConcursosApiService($token, $user->username);
            return $api->getTiposDocumentosOferta($this->concurso->id) ?? [];
        } catch (\Exception $e) {
            Log::error('Error getting tipos documentos oferta', [
                'error' => $e->getMessage(),
                'concurso_id' => $this->concurso->id ?? 'unknown'
            ]);
            return [];
        }
    }

    /**
     * Determinar el estado actual del botón principal
     */
    public function getEstadoBoton()
    {
        $intencion = $this->invitacion->intencion ?? 0;
        
        // Si el concurso no está activo o ya pasó la fecha de cierre
        if (!$this->concurso_activo || !$this->antes_del_cierre) {
            return 'inactivo';
        }

        // Estados según intención
        switch ($intencion) {
            case 0: // Sin intención definida
                return 'participar';
            
            case 1: // Con intención de participar
                if ($this->documentacion_completa && $this->obligatorios_completos) {
                    return 'presentar_oferta_completa';
                } elseif (!$this->obligatorios_completos) {
                    return 'falta_documentacion_obligatoria';
                } else {
                    return 'presentar_oferta_faltante';
                }
            
            case 2: // Intención rechazada
                return 'arrepentimiento';
            
            case 3: // Oferta presentada
                return 'oferta_presentada';
            
            default:
                return 'inactivo';
        }
    }

    /**
     * Determinar si se debe mostrar el modal
     */
    public function getMostrarModal()
    {
        $estado = $this->getEstadoBoton();
        return in_array($estado, ['participar', 'presentar_oferta_completa', 'presentar_oferta_faltante', 'arrepentimiento', 'oferta_presentada']);
    }

    /**
     * Dar de baja la oferta
     */
    public function darBajaOferta()
    {
        if (!$this->puedeDarBaja()) {
            $this->addError('baja', 'No se puede dar de baja la oferta.');
            return;
        }

        if ($this->concursoCerrado()) {
            $this->addError('baja', 'No se puede dar de baja la oferta de un concurso cerrado.');
            return;
        }

        if (empty($this->password)) {
            $this->addError('password', 'Debe ingresar su contraseña para confirmar la acción.');
            return;
        }

        // Validar la contraseña del usuario autenticado
        if (!\Illuminate\Support\Facades\Hash::check($this->password, \Illuminate\Support\Facades\Auth::user()->password)) {
            $this->addError('password', 'La contraseña ingresada es incorrecta.');
            return;
        }

        $this->dando_baja = true;

        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);
            
            $success = $api->darBajaOferta($this->concurso->id);

            if ($success) {
                Log::info('Oferta dada de baja exitosamente', [
                    'user_id' => Auth::id(),
                    'concurso_id' => $this->concurso->id
                ]);

                $this->open = false;
                $this->dando_baja = false;
                $this->password = '';
                
                $this->dispatch('oferta-dada-baja', [
                    'concurso_id' => $this->concurso->id
                ]);

                session()->flash('success', 'Oferta dada de baja correctamente. Todos los documentos han sido eliminados.');
            } else {
                Log::error('Error al dar de baja la oferta', [
                    'user_id' => Auth::id(),
                    'concurso_id' => $this->concurso->id
                ]);

                $this->addError('baja', 'Error al dar de baja la oferta. Intente nuevamente.');
            }

        } catch (\Exception $e) {
            Log::error('Excepción al dar de baja la oferta', [
                'user_id' => Auth::id(),
                'concurso_id' => $this->concurso->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addError('baja', 'Error temporal del sistema. Intente nuevamente.');
        }

        $this->dando_baja = false;
    }

    /**
     * Verificar si el concurso está cerrado
     */
    private function concursoCerrado()
    {
        if (!$this->concurso) {
            return true;
        }

        if (isset($this->concurso->estado) && is_array($this->concurso->estado)) {
            if (isset($this->concurso->estado['id']) && $this->concurso->estado['id'] == 3) {
                return true;
            }
        }

        if (isset($this->concurso->fecha_cierre)) {
            $fechaCierre = is_string($this->concurso->fecha_cierre) 
                ? Carbon::parse($this->concurso->fecha_cierre)
                : $this->concurso->fecha_cierre;
            
            if (now()->gte($fechaCierre)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si puede dar de baja la oferta
     */
    private function puedeDarBaja()
    {
        if ($this->concursoCerrado()) {
            return false;
        }

        if ($this->invitacion && isset($this->invitacion->intencion)) {
            return $this->invitacion->intencion == 3;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.concursos.action-modal');
    }
}