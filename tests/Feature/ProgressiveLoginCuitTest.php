<?php

use App\Livewire\Auth\ProgressiveLogin;
use App\Models\User;
use Livewire\Livewire;

test('un cuit alfanumerico valido de 6 a 20 caracteres pasa la validacion', function () {
    Livewire::test(ProgressiveLogin::class)
        ->set('cuit', 'ABC123XYZ')
        ->call('searchUser')
        ->assertHasNoErrors('cuit');
});

test('un cuit de menos de 6 caracteres es rechazado', function () {
    Livewire::test(ProgressiveLogin::class)
        ->set('cuit', 'AB12')
        ->call('searchUser')
        ->assertHasErrors(['cuit' => 'min']);
});

test('un cuit de mas de 20 caracteres es rechazado', function () {
    Livewire::test(ProgressiveLogin::class)
        ->set('cuit', str_repeat('A', 21))
        ->call('searchUser')
        ->assertHasErrors(['cuit' => 'max']);
});

test('un cuit con simbolos o espacios se sanea aunque se saltee el filtro de js', function () {
    $component = Livewire::test(ProgressiveLogin::class)
        ->set('cuit', 'ab-12 34!!');

    expect($component->get('cuit'))->toBe('AB1234');
});

test('un cuit en minusculas se normaliza a mayusculas antes de buscar usuario', function () {
    $user = User::factory()->create(['username' => 'ABC123XYZ']);

    Livewire::test(ProgressiveLogin::class)
        ->set('cuit', 'abc123xyz')
        ->call('searchUser')
        ->assertSet('step', 'user_found');

    expect($user->username)->toBe('ABC123XYZ');
});
