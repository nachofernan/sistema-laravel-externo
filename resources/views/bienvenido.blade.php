<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 py-6">
        <div class="w-full max-w-2xl p-8 bg-white rounded-2xl shadow-xl">
            {{-- <a href="{{route('login')}}"  --}}
            <a href="https://buenosairesenergia.com.ar/registroproveedores/login" 
               class="inline-flex items-center text-blue-600 hover:underline mb-4"
               aria-label="Volver al sitio principal">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Volver al Registro de Proveedores
            </a>
            <div class="mb-6 text-center flex flex-col items-center">
                <x-authentication-card-logo />
                <h2 class="text-2xl font-bold text-gray-900 mt-4">Solicitud de Registro de Proveedores</h2>
                <p class="text-gray-600 mt-2">Completá el siguiente formulario para iniciar el proceso de registro.</p>
                
            </div>
            <form id="formulario" method="POST" action="{{ route('recibidos') }}" class="space-y-4">
                @csrf
                <h3 class="text-lg font-semibold text-gray-800">Datos del solicitante</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre Completo *</label>
                    <input type="text" name="nombre_y_apellido" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo *</label>
                        <input type="text" name="tipo" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Número de Documento *</label>
                        <input type="text" name="numero" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Correo Electrónico Personal *</label>
                    <input type="email" name="correo_personal" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mt-6">Datos del proveedor</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Razón social *</label>
                    <input type="text" name="razonsocial" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">CUIT/CUIL *</label>
                    <input type="text" name="cuit" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Correo Electrónico Institucional *</label>
                    <input type="email" name="correo_institucional" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                    <input type="text" name="telefono" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Dirección</label>
                    <input type="text" name="direccion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex items-center space-x-2 mt-4">
                    <input type="checkbox" id="firma" name="firma" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="firma" class="text-sm text-gray-700">Active la casilla para validar el formulario</label>
                </div>

                <div class="mt-6">
                    <button type="submit" id="btn_envio" disabled class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Enviar Formulario
                    </button>
                </div>
                <div style="display: none;" aria-hidden="true">
                    <input type="text" name="verification_code" value="">
                </div>
            </form>
        </div>
    </div>
    <script>
        const firma = document.getElementById('firma');
        const btnEnvio = document.getElementById('btn_envio');
        firma.addEventListener('change', () => {
            btnEnvio.disabled = !firma.checked;
        });
    </script>
</x-guest-layout>
