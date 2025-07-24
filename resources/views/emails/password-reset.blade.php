@component('mail::message')
# Solicitud de recuperación de contraseña

Hemos recibido una solicitud para restablecer la contraseña de su cuenta.

Para continuar con el proceso, haga clic en el siguiente botón:

@component('mail::button', ['url' => route('password.reset', ['token' => $token, 'username' => $user->username])])
Restablecer Contraseña
@endcomponent

Si usted no solicitó este cambio, puede ignorar este mensaje.

Este enlace expirará en 1 hora por razones de seguridad.

Gracias,<br>
{{ config('app.name') }}
@endcomponent