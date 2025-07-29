<div>
    {{-- The Master doesn't talk, he acts. --}}
    
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <h2 class="text-xl font-semibold text-gray-700">Rubros y Subrubros</h2>
        <button class="text-blue-600 hover:underline" wire:click="$set('open', true)">
            Editar Rubros y Subrubros
        </button>
    </div>
    
    <div class="text-sm px-4">
        @php
            // Usar datos cacheados del componente
            $subrubrosGrouped = collect($subrubros)->groupBy(function($item) {
                $sub = is_array($item) ? (object) $item : $item;
                // Buscar en los datos cacheados
                $rubroNombre = 'Sin rubro';
                if (isset($sub->id)) {
                    foreach ($this->rubrosData as $rubro) {
                        foreach ($rubro['subrubros'] ?? [] as $subrubroData) {
                            if ($subrubroData['id'] == $sub->id) {
                                $rubroNombre = $rubro['rubro'];
                                break 2;
                            }
                        }
                    }
                }
                return $rubroNombre;
            });
        @endphp
        
        @foreach ($subrubrosGrouped as $rubro => $subrubrosDelRubro)
            <div class="mb-4">
                <div class="font-semibold text-gray-700 mb-2">{{ $rubro }}</div>
                <div class="pl-4 space-y-1">
                    @foreach ($subrubrosDelRubro as $subrubro)
                        @php
                            $sub = is_array($subrubro) ? (object) $subrubro : $subrubro;
                            // Buscar el nombre del subrubro en datos cacheados
                            $nombreSubrubro = $sub->nombre ?? '';
                            if (empty($nombreSubrubro) && isset($sub->id)) {
                                foreach ($this->rubrosData as $rubro) {
                                    foreach ($rubro['subrubros'] ?? [] as $subrubroData) {
                                        if ($subrubroData['id'] == $sub->id) {
                                            $nombreSubrubro = $subrubroData['subrubro'];
                                            break 2;
                                        }
                                    }
                                }
                            }
                        @endphp
                        <div class="text-gray-600">- {{ $nombreSubrubro }}</div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <x-dialog-modal wire:model="open" maxWidth="7xl"> 
        <div class="max-w-10xl">
        <x-slot name="title"> 
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Editar Rubros y Subrubros</h2>
                <input type="text" wire:model.live="search" class="w-1/2 rounded border-gray-600" placeholder="Buscar">
                <button wire:click="$set('open', false)" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>             
        </x-slot> 
        
        <x-slot name="content">
            @if($loading)
                <div class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-2 text-gray-600">Actualizando...</span>
                </div>
            @endif
            
            @foreach ($resultados as $resultado)
                <div class="grid grid-cols-12 gap-6 border-b py-2"> 
                    <div class="col-span-3 font-bold">
                        {{ $resultado['rubro'] }}
                        <span wire:click="marcarTodos({{ $resultado['id'] }})" 
                              class="text-sm cursor-pointer text-blue-600 hover:underline mx-2 {{ $loading ? 'opacity-50 pointer-events-none' : '' }}">
                            Todos
                        </span>
                    </div>
                    <div class="col-span-9">
                        @foreach ($resultado['subrubros'] ?? [] as $subrubro)
                            @php
                                $isChecked = collect($subrubros)->contains(function($item) use ($subrubro) {
                                    $itemObj = is_array($item) ? (object) $item : $item;
                                    return $itemObj->id == $subrubro['id'];
                                });
                            @endphp
                            <div wire:click="agregarSubrubro({{ $subrubro['id'] }})" 
                                 class="cursor-pointer {{ $loading ? 'opacity-50 pointer-events-none' : '' }}">
                                <input type="checkbox" 
                                       class="mr-2" 
                                       {{ $isChecked ? 'checked' : '' }}
                                       readonly>
                                <span class="text-sm {{ $isChecked ? 'font-semibold text-blue-600' : '' }}">
                                    {{ $subrubro['subrubro'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </x-slot>
        
        <x-slot name="footer">
            <button wire:click="$set('open', false)" class="boton-gris">Cerrar</button>
        </x-slot>
        </div>
    </x-dialog-modal>
</div>