<?php

use App\Livewire\Auth\ProgressiveLogin;
use App\Models\EventoUsuario;
use App\Models\LoginAttempt;
use App\Models\User;
use Livewire\Livewire;

test('un usuario logueado genera un evento de login', function () {
    $user = User::factory()->create();

    Livewire::test(ProgressiveLogin::class)
        ->set('cuit', $user->username)
        ->set('password', 'password')
        ->call('attemptLogin');

    expect(
        EventoUsuario::where('tipo_evento', 'login')
            ->where('user_id', $user->id)
            ->exists()
    )->toBeTrue();
});

test('el logout genera un evento con el user id correcto', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout');

    expect(
        EventoUsuario::where('tipo_evento', 'logout')
            ->where('user_id', $user->id)
            ->exists()
    )->toBeTrue();
});

test('la suspension de cuenta ya no escribe en login attempts', function () {
    $user = User::factory()->create();

    $loginAttemptsAntes = LoginAttempt::count();

    $user->suspend('motivo test');

    expect(
        EventoUsuario::where('tipo_evento', 'suspension_cuenta')
            ->where('user_id', $user->id)
            ->exists()
    )->toBeTrue();

    expect(LoginAttempt::count())->toBe($loginAttemptsAntes);
});
