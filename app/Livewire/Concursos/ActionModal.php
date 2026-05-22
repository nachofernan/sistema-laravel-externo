<?php

namespace App\Livewire\Concursos;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Services\ConcursosApiService;
use Livewire\Attributes\Computed;

class ActionModal extends Component
{
    public $open = false;
    public $concurso;
    public $invitacion;
    public $documentacion_completa = false;
    public $obligatorios_completos = false;

    // Propiedades para dar de baja la oferta
    public $dando_baja = false;
    public $password = '';

    // Propiedades para el flujo de rechazo con motivo
    public $mostrando_motivo = false;
    public $motivo_seleccionado = '';
    public $motivo_texto = '';

    public function mount($concurso, $invitacion)
    {
        $this->concurso = $concurso;
        $this->invitacion = $invitacion;
        $this->checkDocumentacion();
    }

    /**
     * Determina el estado del concurso (activo y antes del cierre)
     */
    #[Computed]
    public function isActivo()
    {
        $estadoActual = is_array($this->concurso->estado) 
            ? ($this->concurso->estado['estado_actual'] ?? '') 
            : ($this->concurso->estado->estado_actual ?? '');

        $antesDelCierre = isset($this->concurso->fecha_cierre) && 
            Carbon::parse($this->concurso->fecha_cierre)->isFuture();

        return $estadoActual === 'activo' && $antesDelCierre;
    }

    /**
     * Determina el estado actual del flujo basado en la intención
     */
    #[Computed]
    public function estado()
    {
        if (!$this->isActivo) {
            return 'inactivo';
        }

        $intencion = (int) ($this->invitacion->intencion ?? 0);

        return match ($intencion) {
            0 => 'pendiente',
            1 => $this->obligatorios_completos 
                ? ($this->documentacion_completa ? 'listo_completo' : 'listo_parcial') 
                : 'falta_obligatorios',
            2 => 'rechazado',
            3 => 'presentado',
            default => 'inactivo',
        };
    }

    /**
     * Actualiza la intención de participación y redirige para refrescar el layout
     */
    public function updateIntention($value)
    {
        try {
            $user = Auth::user();
            $token = session('jwt_token');
            $api = new ConcursosApiService($token, $user->username);

            if ($api->cambiarIntencion($this->concurso->id, $value)) {
                session()->flash('success', 'Intención actualizada correctamente.');
                return redirect()->route('concursos.show', $this->concurso->id);
            }

            $this->addError('intencion', 'No se pudo actualizar la intención.');
        } catch (\Exception $e) {
            Log::error('Error updating intention', ['error' => $e->getMessage()]);
            $this->addError('intencion', 'Error de conexión con el servidor.');
        }
    }

    public function iniciarRechazo()
    {
        $this->mostrando_motivo = true;
        $this->motivo_seleccionado = '';
        $this->motivo_texto = '';
        $this->resetErrorBag('motivo');
    }

    public function confirmarRechazo()
    {
        $this->validate([
            'motivo_seleccionado' => 'required',
            'motivo_texto' => $this->motivo_seleccionado === 'otro' ? 'required|string|max:950' : 'nullable',
        ], [
            'motivo_seleccionado.required' => 'Debe seleccionar un motivo.',
            'motivo_texto.required' => 'Debe escribir el motivo.',
            'motivo_texto.max' => 'El motivo no puede superar los 950 caracteres.',
        ]);

        $observaciones = $this->motivo_seleccionado === 'otro'
            ? 'Otros: ' . trim($this->motivo_texto)
            : $this->motivo_seleccionado;

        try {
            $user = Auth::user();
            $api = new ConcursosApiService(session('jwt_token'), $user->username);

            try {
                $api->darBajaOferta($this->concurso->id);
            } catch (\Exception $e) {
                Log::warning('Borrado de archivos al rechazar falló o no era necesario', ['error' => $e->getMessage()]);
            }

            if ($api->cambiarIntencion($this->concurso->id, 2, $observaciones)) {
                session()->flash('success', 'Intención actualizada correctamente.');
                return redirect()->route('concursos.show', $this->concurso->id);
            }

            $this->addError('motivo', 'No se pudo actualizar la intención.');
        } catch (\Exception $e) {
            Log::error('Error al confirmar rechazo', ['error' => $e->getMessage()]);
            $this->addError('motivo', 'Error de conexión con el servidor.');
        }
    }
    public function checkDocumentacion()
    {
        try {
            $tipos = collect($this->getTiposDocumentosOferta());

            if ($tipos->isEmpty()) {
                $this->documentacion_completa = true;
                $this->obligatorios_completos = true;
                return;
            }

            $analisis = $tipos->map(function ($tipo) {
                $tieneArchivos = !empty($tipo['documentos_oferta']);
                $tieneAsociacionValida = isset($tipo['tipo_documento_proveedor']['id']) && !is_null($tipo['tipo_documento_proveedor']['id']);
                return [
                    'cubierto' => $tieneArchivos || $tieneAsociacionValida,
                    'obligatorio' => (bool) ($tipo['obligatorio'] ?? false)
                ];
            });

            $faltanObligatorios = $analisis->where('obligatorio', true)->where('cubierto', false)->isNotEmpty();
            $faltanOpcionales = $analisis->where('obligatorio', false)->where('cubierto', false)->isNotEmpty();

            $this->obligatorios_completos = !$faltanObligatorios;
            $this->documentacion_completa = !$faltanObligatorios && !$faltanOpcionales;

        } catch (\Exception $e) {
            Log::error('Error checking documentation', ['error' => $e->getMessage()]);
            $this->documentacion_completa = false;
            $this->obligatorios_completos = false;
        }
    }

    private function getTiposDocumentosOferta()
    {
        if (isset($this->concurso->tipos_documentos_oferta)) {
            return $this->concurso->tipos_documentos_oferta;
        }

        try {
            $api = new ConcursosApiService(session('jwt_token'), Auth::user()->username);
            return $api->getTiposDocumentosOferta($this->concurso->id) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function darBajaOferta()
    {
        if ($this->isCerrado() || ($this->invitacion->intencion ?? 0) != 3) {
            $this->addError('baja', 'Acción no permitida.');
            return;
        }

        $this->validate([
            'password' => ['required', function ($attribute, $value, $fail) {
                if (!Hash::check($value, Auth::user()->password)) {
                    $fail('La contraseña ingresada es incorrecta.');
                }
            }],
        ]);

        $this->dando_baja = true;

        try {
            $api = new ConcursosApiService(session('jwt_token'), Auth::user()->username);
            if ($api->darBajaOferta($this->concurso->id)) {
                $this->open = false;
                session()->flash('success', 'Oferta dada de baja correctamente.');
                return redirect()->route('concursos.show', $this->concurso->id);
            }
            $this->addError('baja', 'Error al dar de baja la oferta.');
        } catch (\Exception $e) {
            $this->addError('baja', 'Error de comunicación con el servidor.');
        } finally {
            $this->dando_baja = false;
        }
    }

    private function isCerrado()
    {
        $estadoId = is_array($this->concurso->estado) ? ($this->concurso->estado['id'] ?? 0) : ($this->concurso->estado->id ?? 0);
        if ($estadoId == 3) return true;

        if (isset($this->concurso->fecha_cierre)) {
            return now()->gte(Carbon::parse($this->concurso->fecha_cierre));
        }

        return false;
    }

    public function render()
    {
        return view('livewire.concursos.action-modal');
    }
}