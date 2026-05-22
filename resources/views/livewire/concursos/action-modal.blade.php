<div class="font-normal" x-data="{ subiendo: false }">
    {{-- Barra de Estado y Botón de Gestión --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4 bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-sm min-w-[500px]">
        <div class="flex-1">
            <p class="text-xs uppercase tracking-wider text-gray-500 font-bold mb-1">Estado de Participación</p>
            <div class="flex items-center">
                @switch($this->estado)
                    @case('pendiente')
                        <span class="flex h-3 w-3 rounded-full bg-blue-500 mr-2"></span>
                        <span class="text-blue-700 font-semibold">Pendiente de Definición</span>
                        @break
                    @case('listo_completo')
                        <span class="flex h-3 w-3 rounded-full bg-gray-500 mr-2 animate-pulse"></span>
                        <span class="text-gray-700 font-semibold">Oferta No Presentada</span>
                        @break
                    @case('listo_parcial')
                        <span class="flex h-3 w-3 rounded-full bg-yellow-500 mr-2"></span>
                        <span class="text-yellow-700 font-semibold">Oferta No Presentada</span>
                        @break
                    @case('falta_obligatorios')
                        <span class="flex h-3 w-3 rounded-full bg-red-400 mr-2"></span>
                        <span class="text-red-600 font-semibold">Falta Documentación Obligatoria</span>
                        @break
                    @case('rechazado')
                        <span class="flex h-3 w-3 rounded-full bg-red-400 mr-2"></span>
                        <span class="text-red-600 font-semibold">Intención Rechazada</span>
                        @break
                    @case('presentado')
                        <span class="flex h-1.5 w-1.5 rounded-full bg-green-600 mr-2"></span>
                        <span class="text-green-800 font-bold uppercase text-sm tracking-tight">Oferta Presentada</span>
                        @break
                    @default
                        <span class="text-gray-600">{{ $invitacion->intencion_texto ?? 'No definido' }}</span>
                @endswitch
            </div>
        </div>

        <div class="flex-shrink-0">
            @if($this->estado !== 'inactivo')
                <button 
                    wire:click="$set('open', true)" 
                    class="px-3 py-2.5 bg-white border-2 border-blue-500 text-blue-600 font-bold rounded-lg hover:bg-blue-50 transition-all duration-200 shadow-sm flex items-center ml-10"
                >
                    <div class="flex flex-col items-center mr-2">
                        <span class="h-1 w-1 bg-blue-500 rounded-full mb-0.5"></span>
                        <span class="h-1 w-1 bg-blue-500 rounded-full mb-0.5"></span>
                        <span class="h-1 w-1 bg-blue-500 rounded-full"></span>
                    </div>
                    Gestionar
                </button>
            @endif
        </div>
    </div>

    {{-- Modal de Acción --}}
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-xl font-semibold text-gray-800">
                    Acciones de Participación
                </h2>
                <button wire:click="$set('open', false)" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="p-4 space-y-6">
                
                {{-- 1. PENDIENTE --}}
                @if($this->estado === 'pendiente' && !$mostrando_motivo)
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                        <p class="text-blue-700 text-sm">Aún no ha definido si participará en este concurso. Es necesario indicar su intención para habilitar la carga de documentos.</p>
                    </div>
                    <div class="flex flex-col space-y-3">
                        <button
                            wire:click="updateIntention(1)"
                            onclick="this.disabled=true; this.innerText='Procesando...';"
                            class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700 transition shadow disabled:opacity-50"
                        >
                            Deseo participar en el Concurso
                        </button>
                        <button
                            wire:click="iniciarRechazo"
                            class="w-full bg-gray-500 text-white font-bold py-3 rounded-lg hover:bg-gray-600 transition shadow"
                        >
                            No participaré
                        </button>
                    </div>
                @endif

                {{-- 2. GESTIÓN DE OFERTA (Interesado pero no presentado) --}}
                @if(in_array($this->estado, ['listo_completo', 'listo_parcial', 'falta_obligatorios']) && !$mostrando_motivo)
                    @if($this->estado === 'listo_completo')
                        <div class="bg-green-50 border-l-4 border-green-500 p-4">
                            <p class="text-green-700 font-medium italic">¡Todo listo! Puede presentar su oferta ahora.</p>
                        </div>
                        <button 
                            wire:click="updateIntention(3)" 
                            onclick="this.disabled=true; this.innerText='Procesando...';"
                            class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700 transition shadow disabled:opacity-50"
                        >
                            Presentar Oferta Completa
                        </button>
                    @elseif($this->estado === 'listo_parcial')
                        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 text-sm">
                            <p class="text-yellow-700 font-medium">Atención: Tiene documentos opcionales pendientes. Puede presentar la oferta así o seguir cargando.</p>
                        </div>
                        <button 
                            wire:click="updateIntention(3)" 
                            onclick="this.disabled=true; this.innerText='Procesando...';"
                            class="w-full bg-yellow-500 text-white font-bold py-3 rounded-lg hover:bg-yellow-600 transition shadow disabled:opacity-50"
                        >
                            Presentar Oferta con Faltantes
                        </button>
                    @else
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 text-sm">
                            <p class="text-red-700 font-medium">Debe completar la documentación obligatoria antes de presentar la oferta.</p>
                        </div>
                    @endif

                    {{-- Opción de Intención Inversa --}}
                    <div class="pt-6 border-t">
                        <p class="text-sm text-gray-500 mb-3 font-medium uppercase tracking-tight">Cambio de Intención</p>
                        <button
                            wire:click="iniciarRechazo"
                            class="w-full py-3 bg-white border-2 border-red-500 text-red-600 rounded-lg hover:bg-red-50 transition font-bold"
                        >
                            No participaré del Concurso
                        </button>
                    </div>
                @endif

                {{-- PANEL DE MOTIVO DE RECHAZO --}}
                @if($mostrando_motivo)
                    <div class="bg-gray-50 border-l-4 border-gray-400 p-4">
                        <p class="text-gray-700 text-sm font-medium">Seleccione el motivo por el cual no presentará oferta:</p>
                    </div>

                    <div class="space-y-3">
                        @foreach([
                            'Falta de Stock'      => 'Falta de Stock',
                            'Falta de Capacidad Operativa/Agenda' => 'Falta de Capacidad Operativa/Agenda',
                            'Restricciones Geográficas/Logísticas' => 'Restricciones Geográficas/Logísticas',
                            'Incompatibilidad Legal/Administrativa' => 'Incompatibilidad Legal/Administrativa',
                            'No Comercializa el Producto/Servicio' => 'No Comercializa el Producto/Servicio',
                            'otro'           => 'Otro',
                        ] as $valor => $etiqueta)
                            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition {{ $motivo_seleccionado === $valor ? 'border-blue-400 bg-blue-50' : '' }}">
                                <input
                                    type="radio"
                                    wire:model.live="motivo_seleccionado"
                                    value="{{ $valor }}"
                                    class="text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-gray-800 text-sm font-medium">{{ $etiqueta }}</span>
                            </label>
                        @endforeach

                        @if($motivo_seleccionado === 'otro')
                            <div class="mt-2">
                                <textarea
                                    wire:model="motivo_texto"
                                    rows="3"
                                    maxlength="950"
                                    placeholder="Describa el motivo..."
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm resize-none"
                                ></textarea>
                                @error('motivo_texto') <p class="text-red-600 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        @error('motivo_seleccionado') <p class="text-red-600 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                        @error('motivo') <p class="text-red-600 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col space-y-2 pt-2">
                        <button
                            wire:click="confirmarRechazo"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="w-full bg-gray-600 text-white font-bold py-3 rounded-lg hover:bg-gray-700 transition shadow disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="confirmarRechazo">Confirmar — No participaré</span>
                            <span wire:loading wire:target="confirmarRechazo">Procesando...</span>
                        </button>
                        <button
                            wire:click="$set('mostrando_motivo', false)"
                            class="w-full py-2 text-gray-500 text-sm hover:underline"
                        >
                            Cancelar
                        </button>
                    </div>
                @endif

                {{-- 3. RECHAZADO --}}
                @if($this->estado === 'rechazado' && !$mostrando_motivo)
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 text-sm space-y-1">
                        <p class="text-red-700 font-medium">Indicó que no participará en este concurso.</p>
                        @if(!empty($invitacion->observaciones))
                            <p class="text-red-700">
                                Motivo indicado: <span class="font-semibold">{{ $invitacion->observaciones }}</span>
                            </p>
                        @endif
                        <p class="text-red-600 italic text-xs pt-1">En caso de querer revertir esta decisión, haga clic en el botón de abajo.</p>
                    </div>
                    <button
                        wire:click="updateIntention(1)"
                        onclick="this.disabled=true; this.innerText='Procesando...';"
                        class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition shadow disabled:opacity-50"
                    >
                        Cambiar a: Deseo Participar
                    </button>
                @endif

                {{-- 4. PRESENTADO (Dar de baja) --}}
                @if($this->estado === 'presentado')
                    <div class="bg-red-50 border-l-4 border-red-500 p-4">
                        <p class="text-red-700 font-medium mb-1">⚠️ Dar de Baja Oferta</p>
                        <p class="text-red-700 text-sm">Esta acción borrará <strong>TODOS</strong> los archivos subidos. La oferta dejará de estar presentada.</p>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Confirme con su contraseña:</label>
                        <input type="password" wire:model="password" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500" placeholder="********">
                        @error('password') <p class="text-red-600 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                        @error('baja') <p class="text-red-600 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col space-y-3 pt-4">
                        <button 
                            wire:click="darBajaOferta" 
                            onclick="this.disabled=true; this.innerText='Procesando...';"
                            class="w-full py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition shadow disabled:opacity-50"
                        >
                            Dar de Baja Oferta Presentada
                        </button>
                        <button wire:click="$set('open', false)" class="w-full py-2 text-gray-500 text-sm hover:underline">Cancelar</button>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer"></x-slot>
    </x-dialog-modal>
</div>
