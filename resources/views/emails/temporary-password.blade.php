@component('mail::message')
# Bienvenido al Sistema de Concursos

Se ha generado una contraseña temporal para su acceso al sistema.

Su contraseña temporal es: **{{ $temporaryPassword }}**

Por favor, ingrese al sistema utilizando su CUIT como nombre de usuario y esta contraseña temporal.
Le recomendamos cambiar esta contraseña una vez que haya ingresado al sistema.

@component('mail::button', ['url' => route('login')])
Ingresar al Sistema
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent