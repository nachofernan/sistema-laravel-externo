<x-app-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf

            <div class="mt-4">
                <x-label for="current_password" value="{{ __('Contraseña Actual') }}" />
                <x-input id="current_password" class="block mt-1 w-full" type="password" name="current_password" required autocomplete="off" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Nueva Contraseña') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="off" />
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation" value="{{ __('Confirmar Nueva Contraseña') }}" />
                <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="off" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button class="ml-4">
                    {{ __('Cambiar Contraseña') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-app-layout>