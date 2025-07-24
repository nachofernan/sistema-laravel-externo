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

        @if (session('status'))
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ session('status') }}
            </div>
        @endif

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="block">
                <x-label for="username" value="{{ __('CUIT / DNI') }}" />
                <x-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required autofocus />
            </div>

            <div class="flex items-center justify-between mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 mr-3 focus:ring-indigo-500" href="{{ route('login') }}">
                    {{ __('Volver al Ingreso') }}
                </a>
                <x-button>
                    {{ __('Enviar enlace de recuperación') }}
                </x-button>
            </div>
            <div class="text-sm p-3 bg-gray-100 text-gray-500 border-l-4 border-gray-400 mt-4">
                ¿Olvidó su contraseña? Ingrese su CUIT y le enviaremos un enlace para restablecerla.
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>