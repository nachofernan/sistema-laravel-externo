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

        <x-validation-errors class="mb-4" />

        @if (session('status'))
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <x-label for="username" value="{{ __('CUIT') }}" />
                <x-input id="username" class="block mt-1 w-full" 
                    type="text" 
                    name="username" 
                    :value="old('username')" 
                    required 
                    autofocus 
                    autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Contraseña') }}" />
                <x-input id="password" class="block mt-1 w-full"
                    type="password"
                    name="password"
                    required 
                    autocomplete="current-password" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 mr-3 focus:ring-indigo-500" href="{{ route('provider.register') }}">
                    {{ __('Registrarme') }}
                </a>
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('¿Olvidó su contraseña?') }}
                    </a>
                @endif

                <x-button class="ml-4">
                    {{ __('Ingresar') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>