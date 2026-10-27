<?php

use App\Http\Controllers\SolicitudRegistroController;
use App\Mail\RegistroProveedor;
use App\Rules\Turnstile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    config(['services.turnstile.secret_key' => 'secreto-de-prueba']);
});

function solicitudValida(array $cambios = []): array
{
    return array_merge([
        'nombre_y_apellido' => 'Juana Pérez',
        'tipo' => 'DNI',
        'numero' => '30123456',
        'correo_personal' => 'juana@example.com',
        'razonsocial' => 'Pérez SRL',
        'cuit' => '30712345678',
        'correo_institucional' => 'compras@perezsrl.com',
        'telefono' => '1144445555',
        'direccion' => 'Calle Falsa 123',
        'firma' => '1',
        'cf-turnstile-response' => 'token-de-prueba',
    ], $cambios);
}

function cloudflareAprueba(): void
{
    Http::fake([Turnstile::URL_VERIFICACION => Http::response(['success' => true])]);
}

test('una solicitud valida envia el mail solo a infoproveedores', function () {
    cloudflareAprueba();

    $this->post('/recibidos', solicitudValida())
        ->assertRedirect(route('gracias'));

    Mail::assertSent(RegistroProveedor::class, 1);
    Mail::assertSent(RegistroProveedor::class, function (RegistroProveedor $mail) {
        return $mail->hasTo(SolicitudRegistroController::DESTINATARIO)
            && ! $mail->hasTo('juana@example.com')
            && count($mail->to) === 1;
    });
});

test('el mail no incluye campos que no son del formulario', function () {
    cloudflareAprueba();

    $this->post('/recibidos', solicitudValida(['inyectado' => 'http://spam.example']));

    Mail::assertSent(RegistroProveedor::class, function (RegistroProveedor $mail) {
        return array_keys($mail->datos) === [
            'nombre_y_apellido', 'tipo', 'numero', 'correo_personal', 'razonsocial',
            'cuit', 'correo_institucional', 'telefono', 'direccion',
        ];
    });
});

test('la verificacion manda a cloudflare la clave secreta y el token', function () {
    cloudflareAprueba();

    $this->post('/recibidos', solicitudValida());

    Http::assertSent(function (HttpRequest $request) {
        return $request->url() === Turnstile::URL_VERIFICACION
            && $request['secret'] === 'secreto-de-prueba'
            && $request['response'] === 'token-de-prueba';
    });
});

test('un honeypot completado redirige a gracias sin enviar mail', function () {
    Http::fake();

    $this->post('/recibidos', solicitudValida(['verification_code' => 'soy-un-bot']))
        ->assertRedirect(route('gracias'));

    Mail::assertNothingSent();
    Http::assertNothingSent();
});

test('un token de turnstile invalido rechaza el envio', function () {
    Http::fake([Turnstile::URL_VERIFICACION => Http::response([
        'success' => false,
        'error-codes' => ['invalid-input-response'],
    ])]);

    $this->post('/recibidos', solicitudValida())
        ->assertSessionHasErrors('cf-turnstile-response');

    Mail::assertNothingSent();
});

test('si cloudflare no responde se rechaza el envio', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $this->post('/recibidos', solicitudValida())
        ->assertSessionHasErrors('cf-turnstile-response');

    Mail::assertNothingSent();
});

test('si cloudflare devuelve error 500 se rechaza el envio', function () {
    Http::fake([Turnstile::URL_VERIFICACION => Http::response('', 500)]);

    $this->post('/recibidos', solicitudValida())
        ->assertSessionHasErrors('cf-turnstile-response');

    Mail::assertNothingSent();
});

test('sin token de turnstile se rechaza el envio sin consultar a cloudflare', function () {
    Http::fake();

    $this->post('/recibidos', solicitudValida(['cf-turnstile-response' => '']))
        ->assertSessionHasErrors('cf-turnstile-response');

    Mail::assertNothingSent();
    Http::assertNothingSent();
});

test('campos obligatorios faltantes o emails invalidos se rechazan', function () {
    cloudflareAprueba();

    $this->post('/recibidos', solicitudValida([
        'nombre_y_apellido' => '',
        'razonsocial' => '',
        'correo_personal' => 'no-es-un-mail',
        'correo_institucional' => 'tampoco',
    ]))->assertSessionHasErrors([
        'nombre_y_apellido', 'razonsocial', 'correo_personal', 'correo_institucional',
    ]);

    Mail::assertNothingSent();
});

test('un texto demasiado largo se rechaza', function () {
    cloudflareAprueba();

    $this->post('/recibidos', solicitudValida(['nombre_y_apellido' => str_repeat('a', 151)]))
        ->assertSessionHasErrors('nombre_y_apellido');

    Mail::assertNothingSent();
});

test('sin aceptar la firma se rechaza el envio', function () {
    cloudflareAprueba();

    $datos = solicitudValida();
    unset($datos['firma']);

    $this->post('/recibidos', $datos)
        ->assertSessionHasErrors('firma');

    Mail::assertNothingSent();
});

test('un cuit con guiones se normaliza antes de enviar', function () {
    cloudflareAprueba();

    $this->post('/recibidos', solicitudValida(['cuit' => '30-71234567-8']))
        ->assertRedirect(route('gracias'));

    Mail::assertSent(RegistroProveedor::class, fn ($mail) => $mail->datos['cuit'] === '30712345678');
});

test('un cuit demasiado corto se rechaza', function () {
    cloudflareAprueba();

    $this->post('/recibidos', solicitudValida(['cuit' => '12-3']))
        ->assertSessionHasErrors('cuit');

    Mail::assertNothingSent();
});

test('el mail se arma bien sin telefono ni direccion', function () {
    $datos = solicitudValida();
    unset($datos['telefono'], $datos['direccion'], $datos['firma'], $datos['cf-turnstile-response']);

    expect((new RegistroProveedor($datos))->render())->toContain('Pérez SRL');
});
