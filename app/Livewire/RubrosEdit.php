<?php

namespace App\Livewire;

use App\Services\ProveedorApiService;
use Livewire\Component;

class RubrosEdit extends Component
{
    public $proveedor;
    public $subrubros = [];
    public $search = '';
    public $open = false;
    public $rubrosData = [];
    public $loading = false;

    public function mount($proveedor)
    {
        $this->proveedor = is_array($proveedor) ? (object) $proveedor : $proveedor;
        $this->subrubros = collect($this->proveedor->subrubros ?? []);
        $this->loadRubrosData();
    }

    public function loadRubrosData()
    {
        $api = new ProveedorApiService();
        $this->rubrosData = $api->getRubros();
    }

    public function agregarSubrubro($subrubroId)
    {
        if ($this->loading) {
            return;
        }

        $this->loading = true;
        
        // Actualizar estado local inmediatamente
        $ids = collect($this->subrubros)->pluck('id')->toArray();
        if (in_array($subrubroId, $ids)) {
            $ids = array_diff($ids, [$subrubroId]);
        } else {
            $ids[] = $subrubroId;
        }
        
        $this->subrubros = collect($ids)->map(function($id) {
            return (object) ['id' => $id];
        });

        // Llamada API
        $api = new ProveedorApiService();
        $ok = $api->setSubrubros(array_values($ids));
        
        if ($ok) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Subrubro actualizado correctamente'
            ]);
        } else {
            // Revertir cambios en caso de error
            $this->subrubros = collect($this->proveedor->subrubros ?? []);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al actualizar subrubro'
            ]);
        }
        
        $this->loading = false;
    }

    public function marcarTodos($rubroId)
    {
        if ($this->loading) {
            return;
        }

        $this->loading = true;
        
        $rubroData = collect($this->rubrosData)->firstWhere('id', $rubroId);
        
        if (!$rubroData) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Rubro no encontrado'
            ]);
            $this->loading = false;
            return;
        }

        $subrubrosRubroIds = collect($rubroData['subrubros'] ?? [])->pluck('id')->toArray();
        $actuales = collect($this->subrubros)->pluck('id')->toArray();
        $marcar = count(array_diff($subrubrosRubroIds, $actuales)) > 0;
        
        $ids = $marcar
            ? array_unique(array_merge($actuales, $subrubrosRubroIds))
            : array_diff($actuales, $subrubrosRubroIds);
            
        // Actualizar estado local inmediatamente
        $this->subrubros = collect($ids)->map(function($id) {
            return (object) ['id' => $id];
        });

        // Llamada API
        $api = new ProveedorApiService();
        $ok = $api->setSubrubros(array_values($ids));
        
        if ($ok) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Rubros actualizados correctamente'
            ]);
        } else {
            // Revertir cambios en caso de error
            $this->subrubros = collect($this->proveedor->subrubros ?? []);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al actualizar rubros'
            ]);
        }
        
        $this->loading = false;
    }

    public function render()
    {
        // Filtrar por búsqueda si existe
        $resultados = collect($this->rubrosData);
        if (!empty($this->search)) {
            $resultados = $resultados->filter(function($rubro) {
                // Buscar en el nombre del rubro
                if (stripos($rubro['rubro'], $this->search) !== false) {
                    return true;
                }
                // Buscar en los subrubros
                return collect($rubro['subrubros'] ?? [])->contains(function($subrubro) {
                    return stripos($subrubro['subrubro'], $this->search) !== false;
                });
            });
        }
        
        return view('livewire.rubros-edit', compact('resultados'));
    }
}