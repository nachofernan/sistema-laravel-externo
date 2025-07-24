<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <div class="flex justify-center">
                <x-authentication-card-logo />
            </div>
            <div class="text-center text-gray-600 font-bold border-t pt-2 mt-2">
                Portal de Acceso de Proveedores Externos
            </div>
        </x-slot>

        @if (session('error'))
            <div class="mb-4 font-medium text-sm text-red-600">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('provider.check-cuit') }}">
            @csrf

            <div>
                <x-label for="cuit" value="{{ __('CUIT') }}" />
                <x-input id="cuit" class="block mt-1 w-full" type="text" name="cuit" :value="old('cuit')" required autofocus autocomplete="off" />
            </div>
            <div class="flex items-center justify-between mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 mr-3 focus:ring-indigo-500" href="{{ route('login') }}">
                    {{ __('Volver al Ingreso') }}
                </a>
                <x-button class="ml-4">
                    {{ __('Verificar CUIT') }}
                </x-button>
            </div>
            <div class="text-sm p-3 bg-gray-100 text-gray-500 border-l-4 border-gray-400 mt-4">
                Al verificar que el CUIT se encuentre en nuestra base de datos, se le enviará un correo a la casilla registrada con una contraseña provisoria.
                <br>
                En caso de que no exista el registro en nuestra base de datos, podrá solicitar el mismo mediante 
                <a href="https://buenosairesenergia.com.ar/registroproveedores" class="underline">este formulario</a>.
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>