<div>
    {{-- If your happiness depends on money, you will never be happy with yourself. --}}
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">

        <!-- Logo y Header -->
        <div class="mb-6 text-center">
            <div class="flex flex-col items-center">
                <x-authentication-card-logo />
                <div class="text-gray-600 font-bold border-t pt-2 mt-2">
                    Portal de Acceso de Proveedores Externos
                </div>
            </div>
        </div>
    
        <!-- Form Card Principal -->
        <div class="w-full sm:max-w-md px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            
            <!-- Mensajes de Estado -->
            @if($message['text'])
            <div class="mb-4 border rounded-md p-3 text-sm
                @if($message['type'] === 'success') bg-green-50 border-green-200 text-green-800
                @elseif($message['type'] === 'error') bg-red-50 border-red-200 text-red-800
                @elseif($message['type'] === 'info') bg-blue-50 border-blue-200 text-blue-800
                @elseif($message['type'] === 'warning') bg-yellow-50 border-yellow-200 text-yellow-800
                @endif">
                {!! $message['text'] !!}
            </div>
            @endif
    
            <!-- Loading State -->
            @if($step === 'loading')
            <div class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                <span class="ml-3 text-gray-600">Procesando...</span>
            </div>
            @endif
    
            <!-- FORMULARIO PRINCIPAL (siempre visible excepto cuando loading) -->
            @if($step !== 'loading')
            <form wire:submit="searchUser" class="space-y-4 grid grid-cols-10 gap-4">
                <div class="col-span-7">
                    <x-label for="cuit" value="CUIT" />
                    <x-input 
                        id="cuit" 
                        class="block mt-1 w-full" 
                        type="text" 
                        wire:model.live.debounce.500ms="cuit"
                        placeholder="Ingrese su CUIT sin guiones"
                        maxlength="11"
                        required 
                        autofocus 
                        autocomplete="off"
                    />
                    @error('cuit') 
                    <span class="text-sm text-red-600 mt-1">{{ $message }}</span> 
                    @enderror
                </div>
    
                <div class="pt-2 col-span-3">
                    <button type="submit" class="block w-full bg-slate-600 hover:bg-slate-700 text-white font-medium py-2 px-4 rounded-md text-center transition duration-150">
                        Buscar
                    </button>
                </div>
            </form>
            @endif
    
            <!-- CASO 1: Usuario existe - pedir contraseña -->
            @if($step === 'user_found')
            <div class="border-t pt-4 mt-4">
                <form wire:submit="attemptLogin" class="space-y-4">
                    <div x-data x-init="$nextTick(() => $refs.password.focus())">
                        <x-label for="password" value="Contraseña" />
                        <x-input 
                            id="password"
                            x-ref="password"
                            class="block mt-1 w-full" 
                            type="password" 
                            wire:model="password"
                            placeholder="Ingrese su contraseña"
                            required 
                        />
                        @error('password') 
                        <span class="text-sm text-red-600 mt-1">{{ $message }}</span> 
                        @enderror
                    </div>
    
                    <div class="pt-2 space-y-3">
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-md text-center transition duration-150">
                            Ingresar
                        </button>
                        
                        <div class="text-center">
                            <button 
                                type="button"
                                wire:click="showPasswordRecovery"
                                class="text-sm text-gray-600 hover:text-gray-900 underline">
                                ¿Olvidó su contraseña?
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            @endif
    
            <!-- CASO 2: Existe en base interna pero no como usuario -->
            @if($step === 'user_internal_only')
            <div class="border-t pt-4 mt-4">
                <button 
                    wire:click="sendTemporaryPassword"
                    wire:loading.attr="disabled"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 disabled:opacity-50">
                    <span wire:loading.remove>Enviar Contraseña Provisoria al Email</span>
                    <span wire:loading>Enviando...</span>
                </button>
            </div>
            @endif
    
            <!-- CASO 3: No existe en ningún lado -->
            @if($step === 'user_not_found')
            <div class="border-t pt-4 mt-4 space-y-3">
                <div class="text-sm text-gray-600 text-center">
                    Para solicitar acceso al portal, complete el formulario de registro.
                </div>
                
                <a href="{{ route('bienvenido') }}" 
                   class="block w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md text-center transition duration-150">
                    Completar Formulario de Registro
                </a>
            </div>
            @endif
    
            <!-- Footer informativo -->
            <div class="text-xs text-gray-500 text-center mt-6 pt-4 border-t">
                Si tiene problemas para acceder, contacte al administrador del sistema.
                <a href="{{ route('bienvenido') }}"
                   class="block pt-2 px-4 text-blue-600 hover:underline">
                    Completar Formulario de Registro
                </a>
            </div>
        </div>

        <!-- MODAL DE RECUPERACIÓN DE CONTRASEÑA -->
        @if($showPasswordRecoveryModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closePasswordRecoveryModal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" wire:click.stop>
                <!-- Header del Modal -->
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Recuperar Contraseña</h3>
                    <button 
                        wire:click="closePasswordRecoveryModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Contenido del Modal -->
                <div class="mt-4">
                    @if($recoveryEmailSent)
                        <!-- Mensaje de éxito -->
                        <div class="text-center py-4">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">¡Correo Enviado!</h4>
                            <p class="text-sm text-gray-600">
                                Se ha enviado un enlace de recuperación a su correo electrónico registrado. 
                                Revise su bandeja de entrada y siga las instrucciones para restablecer su contraseña.
                            </p>
                        </div>
                    @else
                        <!-- Formulario de recuperación -->
                        <div>
                            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <p class="text-sm text-blue-800">
                                    <span class="font-medium">CUIT:</span> {{ $cuit }}
                                </p>
                                <p class="text-sm text-blue-600 mt-1">
                                    Se enviará el enlace de recuperación al correo registrado para este CUIT.
                                </p>
                            </div>
                            
                            @error('general')
                                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                                    <p class="text-red-600 text-sm">{{ $message }}</p>
                                </div>
                            @enderror
                            
                            <div class="flex justify-end space-x-3">
                                <button 
                                    type="button"
                                    wire:click="closePasswordRecoveryModal"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                                    Cancelar
                                </button>
                                <button 
                                    type="button"
                                    wire:click="sendPasswordRecoveryLink"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50">
                                    <span wire:loading.remove>Enviar Enlace</span>
                                    <span wire:loading>Enviando...</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    
        <!-- JavaScript para eventos y funcionalidad -->
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('redirect-to', (url) => {
                    setTimeout(() => {
                        window.location.href = url[0];
                    }, 1500);
                });
    
                Livewire.on('focus-password', () => {
                    setTimeout(() => {
                        const passwordField = document.getElementById('password');
                        if (passwordField) {
                            passwordField.focus();
                        }
                    }, 100);
                });

                Livewire.on('recovery-email-sent', () => {
                    // Cerrar el modal después de 3 segundos
                    setTimeout(() => {
                        @this.closePasswordRecoveryModal();
                    }, 3000);
                });
            });
        </script>
    </div>
</div>