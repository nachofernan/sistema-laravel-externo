# Vulnerabilidades conocidas — Portal de Proveedores (app externa)

Documento **vivo**: se actualiza cuando se corrige un hallazgo (se marca como resuelto, no se
borra) o aparece uno nuevo. No es un pentest formal — es la lectura de código hecha el 2026-08-18 al
retomar el proyecto con Claude Code. Todo lo marcado "a verificar en servidor" no se puede confirmar
solo leyendo el repo local: hace falta chequear el deployment público real.

Severidad: **Crítico** (compromete el sistema interno o filtra credenciales/secretos) ·
**Alto** (compromete cuentas o datos de proveedores) · **Medio** · **Bajo/Informativo**.

---

## Crítico

### V1 — La app pública posee el secreto que firma los JWT de confianza con el sistema interno

`app/Http/Middleware/RefreshJwtToken.php:27` decodifica el JWT localmente con
`JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'))` para chequear su expiración antes de
pedir uno nuevo. Eso obliga a que `JWT_SECRET` — la clave simétrica HS256 que la API interna usa
para *firmar y validar* esos tokens — exista en el `.env` de esta app, la única pieza del sistema
BAESA expuesta a internet libre.

**Impacto:** si este proceso se compromete (RCE, LFI, backup de `.env` expuesto, etc.), el atacante
no solo roba tokens ya emitidos: se lleva la clave para **forjar tokens HS256 válidos de cualquier
CUIT**, indistinguibles de uno legítimo para la API interna. Es la contradicción directa del
supuesto de diseño ("todo lo sensible está en el interno, acá solo se pide por API"): el secreto que
sostiene esa frontera vive en el lado expuesto.

**Recomendación:** evaluar si la API interna puede exponer un endpoint de validación de expiración
(`GET /api/token/valido` o similar) para que esta app deje de necesitar el secreto para nada más que
enviar el `Bearer`. Si no es viable a corto plazo, tratar `JWT_SECRET` como el secreto más sensible
del `.env`, rotarlo si alguna vez hay sospecha de compromiso, y considerar a mediano plazo migrar a
RS256 (par asimétrico) donde esta app solo tenga la clave **pública** de verificación.

---

### V2 — Conexiones SQL directas y activas a bases internas (`proveedores`, `concursos`)

`config/database.php` configura conexiones MySQL completas a `proveedores` y `concursos`, con
credenciales propias en `.env` (`DB_USERNAME_PROVEEDORES`, `DB_PASSWORD_CONCURSOS`, etc.), y hay
~20 modelos Eloquent (`app/Models/Proveedores/*`, `app/Models/Concursos/*`) que las usan.

**Estado (2026-08-21): cerrado el call site del login.** `app/Livewire/Auth/ProgressiveLogin.php`
(que resultó ser el componente montado en `GET /login`, no un duplicado sin usar — ver V10) migró
sus 3 puntos a `POST /api/validate-provider` vía `ProveedorApiService::validateProvider()`.
`ProgressiveLoginController::checkCuitForRegistration`, que tenía el otro call site activo, se
eliminó por completo (era un endpoint huérfano, ver V10).

**Queda abierto:**
- `app/Http/Controllers/Auth/ProviderRegistrationController.php:37` — sin rutas activas hoy
  (comentadas en `routes/web.php`), pero el código y el call site siguen ahí.
- Los ~20 modelos Eloquent de `Proveedores/`/`Concursos/` y las conexiones en
  `config/database.php`/`.env` — no se borran hasta que no quede ningún call site.

**Impacto:** contradice el modelo "esta app solo habla con el interno por API" y multiplica el radio
de impacto de cualquier compromiso del proceso PHP: en vez de que un atacante quede limitado a lo
que la API interna permite (rate-limited, con contrato y logging propio), obtiene credenciales SQL
directas contra bases internas.

**Recomendación:** decidir si `ProviderRegistrationController` sigue teniendo motivo de existir
(sus rutas ya están comentadas) o se borra entero; una vez sin call sites, eliminar las conexiones
`proveedores`/`concursos` de `config/database.php` y sus credenciales del `.env`, y borrar los
modelos legacy sin uso.

---

### V3 — `APP_DEBUG=true` / `APP_ENV=local` (a verificar en el servidor público real)

El `.env` de este working directory tiene `APP_ENV=local` y `APP_DEBUG=true`. Eso es normal para
desarrollo, pero si el deployment público replica esta configuración, **cualquier excepción no
capturada muestra la página de debug de Laravel (Ignition) con stack trace completo, variables de
entorno resueltas (contraseñas de DB, `JWT_SECRET`, credenciales de Microsoft Graph) y rutas del
filesystem** a cualquier visitante que logre disparar un error.

**Estado:** no verificable desde este repo local — depende de la config del servidor real.

**Recomendación:** confirmar explícitamente en el servidor de producción que `APP_ENV=production` y
`APP_DEBUG=false`. Si alguna vez estuvo mal configurado y se corrigió, dejar constancia acá con
fecha.

---

## Alto

### V4 — El JWT se loguea en texto plano en múltiples puntos

**Estado (2026-08-25): cerrado por completo.** Los tres puntos que quedaban abiertos desde el
2026-08-21 se corrigieron durante un relevamiento general de `Log::` (ver también V11):

```
app/Http/Controllers/ConcursoController.php:36 y 108     ya no loguean 'jwt_token' ni 'user' (objeto completo)
app/Http/Controllers/ProveedorController.php:32          ya no loguea 'jwt_token' ni 'user' (objeto completo)
app/Services/ConcursosApiService.php  (10 llamadas)       ya no loguean 'token'; se dejó 'token_length'
```

**Impacto (histórico):** cualquiera con acceso a `storage/logs/laravel.log` (backup mal expuesto,
acceso al hosting, un LFI en cualquier otra parte del sistema) obtenía tokens JWT válidos y podía
impersonar proveedores contra la API interna mientras el token no expirara (~10 minutos, pero se
auto-refresca antes de vencer mientras la sesión esté activa, así que el log podía quedar con una
cadena continua de tokens válidos a lo largo de una sesión larga).

Ver entrada en "Resueltos" al final del documento.

---

### V5 — `User::$guarded = false` (mass assignment abierto)

`app/Models/User.php:20`. Contradice explícitamente la regla que el propio proyecto se dio ("nunca
`$guarded = []`"). Hoy no hay un `User::create($request->all())` explotable — los tres call sites de
`User::create()` pasan arrays explícitos — pero el modelo tiene columnas sensibles asignables en
masa apenas alguien haga un `fill()`/`update()` con input crudo: `status`, `locked_until`,
`failed_login_attempts`, `must_change_password`, y (vía trait `TwoFactorAuthenticatable`)
`two_factor_secret`/`two_factor_recovery_codes`.

**Recomendación:** declarar `$fillable` explícito con las columnas que de verdad necesitan
asignación masiva (`name`, `username`, `email`, `password` como mucho).

---

## Medio

### V6 — `dd($request->all())` commiteado en `CustomLoginController::login`

`app/Http/Controllers/Auth/CustomLoginController.php:28`. Viola la regla propia del proyecto ("nunca
`dd()` en commits"). La ruta que lo alcanzaría está comentada en `routes/web.php` (bloque
"legacy"), así que hoy es inalcanzable, pero el comentario que la rodea invita explícitamente a
"mantener el sistema anterior temporalmente" descomentándola.

**Recomendación:** sacar el `dd()`. Evaluar si `CustomLoginController` sigue teniendo motivo de
existir junto al `ProgressiveLoginController` activo, o si conviene borrarlo entero.

### V7 — CORS completamente abierto

`config/cors.php`: `allowed_origins => ['*']`, `allowed_methods => ['*']`, `allowed_headers => ['*']`,
aplicado a `api/*` y `sanctum/csrf-cookie`. `supports_credentials` está en `false`, lo que mitiga el
riesgo típico de robo de sesión vía CORS, y `routes/api.php` hoy casi no expone nada. Aun así, es la
config de fábrica de Laravel sin endurecer, en una app pública con sesión.

**Recomendación:** si no hay un consumidor cross-origin real de `/api/*`, restringir
`allowed_origins` a los dominios que de verdad la necesitan (o quitar el wildcard).

### V8 — Enumeración de CUITs/usuarios

- `POST /auth/check-user` devuelve `exists: true/false` para cualquier CUIT.
- `PasswordResetController::sendResetLink` devuelve "No encontramos un usuario con ese CUIT" en vez
  de un mensaje genérico cuando el usuario no existe.
- `checkCuitForRegistration` distingue "ya existe cuenta" / "no es proveedor" / "es proveedor sin
  acceso otorgado", filtrando información sobre qué CUITs son proveedores reales de BAESA.

Es en parte funcional (es el punto del "login progresivo": guiar al usuario paso a paso), y está
mitigado por rate limiting (10 intentos/15min por IP en `check-user`). No es explotable en masa sin
mucho tiempo o muchas IPs, pero permite mapear CUITs válidos con paciencia.

**Recomendación:** trade-off consciente, no necesariamente a corregir. Si se quiere endurecer,
unificar mensajes a algo genérico y aceptar el costo de UX.

### V9 — `SESSION_SECURE_COOKIE` sin definir

`config/session.php:172` usa `env('SESSION_SECURE_COOKIE')`, que no está seteada en `.env` (default
`null`). En una app pública servida por HTTPS, la cookie de sesión debería forzarse `secure=true`
explícitamente para que el navegador nunca la mande por HTTP en texto plano.

**Recomendación:** setear `SESSION_SECURE_COOKIE=true` en el `.env` del deployment público (y
confirmar que el proxy/Apache que termina TLS está bien configurado en `TrustProxies` para que
Laravel sepa que la conexión es segura).

---

## Bajo / informativo

### V10 — Código muerto que aumenta la superficie y la confusión

- ~20 modelos Eloquent de `Proveedores/`/`Concursos/` sin ningún call site real (ver V2).
- **Corrección (2026-08-21):** una entrada anterior de este documento decía que
  `app/Livewire/Auth/ProgressiveLogin.php` no estaba montado en ninguna vista. Era al revés:
  verificado por ruta (`GET /login` → `ProgressiveLoginController::showLoginForm` → vista
  `auth.progressive-login`, que es únicamente `@livewire('auth.progressive-login')`), el componente
  Livewire **es** el login real en producción. Los que estaban muertos eran los tres métodos de
  `ProgressiveLoginController` (`checkUser`, `login`, `checkCuitForRegistration`) y sus rutas
  `POST /auth/check-user`, `/auth/login`, `/auth/check-registration`: registrados como públicos, sin
  auth, duplicando la lógica del componente activo (su propio rate limiting, su propio logueo de
  token, su propio acceso directo a `proveedores`), pero sin ningún consumidor en el frontend
  (confirmado por grep sobre `resources/js` y `resources/views`). Alcanzables igual por URL directa.
  Se eliminaron (métodos, rutas y el logueo de JWT que tenían — ver V2, V4).
- `ConcursoController::index()` llama `$this->testArrayToObjectRecursive()` en **cada** carga de
  `/concursos` — un método de prueba que solo genera datos ficticios. El `Log::info` que tenía
  adentro ya se comentó (ver V11), pero la llamada en sí (línea 27) sigue ejecutándose y generando
  el `$testData` ficticio en cada request sin ningún uso real: queda pendiente decidir si se borra
  el método entero o se le encuentra un propósito.

### V11 — Logging verboso con datos personales de proveedores

**Estado (2026-08-25): mitigado**, en dos pasadas sobre el mismo relevamiento de `Log::` (54
llamadas activas, ver también V4). Criterio adoptado (decisión del dueño del proyecto): solo se
loguea login/logout, acciones de escritura del proveedor (con lo mínimo, sin body/token) y errores
reales de la API. Las lecturas rutinarias (ver un listado, un documento, un concurso) no se loguean
en el camino feliz — se consideran ruido, no una "situación" a preservar.

**Primera pasada** — quitar lo más pesado / sin valor:
- `ConcursoController.php` — `Log::info('Concursos Data Debug', ['raw_data' => $concursosData, ...])`
  (corría en cada `GET /concursos`) y `Log::info('Test ArrayToObjectRecursive', ...)` (corría en
  cada request, ver V10): **comentados**.
- `SubirArchivoGeneral.php` y `Livewire\Concursos\SubirArchivo.php` — 7 `Log::info` de debug puro
  ("Inicia"/"Valida"/objeto `$api`/objeto `UploadedFile`) en cada submit de un documento: **comentados**.

**Segunda pasada** — aplicar el criterio login/escritura-mínima/error vs. lectura-sin-log a
`ConcursosApiService.php`:
- Lecturas puras (`getConcursos`, `getTiposDocumentosConcursos`, `getDocumentosInvitacion`,
  `descargarDocumentoConcurso`, `verificarDocumentoProveedor`): se comentaron los pares
  `Log::info` de "Request/Response Debug" que corrían en cada llamada exitosa. Solo queda el
  `Log::error` de cada método para cuando la API devuelve error.
- Escrituras (`cambiarIntencion`, `subirDocumentoConcurso`, `subirDocumentoAdicional`,
  `eliminarDocumento`, `darBajaOferta`): se comentaron los mismos pares de debug, pero se dejó (o
  agregó, donde no existía) un único `Log::info` de evento mínimo en éxito — solo IDs relevantes
  (`concurso_id`, `documento_tipo_id`, `intencion`), sin `body`/`token`/objetos completos. Sirve
  de rastro ante un reclamo futuro ("subí el documento y no está") sin ser ruido de volumen.

Los `Log::error`/`warning` de excepción real (que sí incluyen `body`/`trace` completo pero solo
disparan ante un fallo, no en el camino feliz) se dejaron como están: son la señal útil cuando algo
se rompe. El log de login exitoso (`ProgressiveLogin.php`, ya reducido a `token_length` desde V4)
tampoco se tocó: es exactamente el tipo de evento que se quiere conservar.

**Recomendación:** si en el futuro hace falta reactivar alguno de los logs comentados para
debuggear un incidente puntual, están comentados (no borrados) — reactivar puntualmente y volver a
comentar al cerrar el incidente, no dejarlos prendidos "por si sirven".

### V12 — Excepción de email hardcodeada a una dirección personal

`ProgressiveLoginController.php:242`, `ProviderRegistrationController.php:59`,
`PasswordResetController.php:49` — fuera de `production`, el mail de contraseña temporal/reset solo
se envía si el destinatario es `@buenosairesenergia.com.ar` o `nachofernan@gmail.com`. Restringe en
vez de abrir envíos (no es una vulnerabilidad), pero es un resabio de testing que vale la pena
limpiar/documentar cuando se consolide el flujo de emails.

### V13 — 2FA de Fortify disponible pero nunca consultado por el login progresivo

`config/fortify.php` tiene `Features::twoFactorAuthentication()` habilitado, y el perfil de usuario
(`resources/views/profile/show.blade.php`) permite activarlo. Pero
`app/Livewire/Auth/ProgressiveLogin.php::attemptLogin()` no consulta
`two_factor_secret`/`two_factor_confirmed_at` antes de hacer `Auth::login()` — el pipeline de
autenticación de Fortify (`Fortify::authenticateUsing`) no está sobreescrito para el login por CUIT.
Un proveedor que activara 2FA en su perfil quedaría con una falsa sensación de protección: el login
progresivo lo deja entrar sin pedirle el segundo factor.

**Estado:** confirmado con el dueño del proyecto que 2FA no se usa hoy en la práctica (nadie lo
activó). No es explotable activamente porque no hay ningún usuario con 2FA activado que dependa de
él, pero es una brecha silenciosa: si algún proveedor lo activara mañana, no se aplicaría.

**Recomendación:** no es urgente mientras no se use. Si en algún momento se decide aprovechar el
2FA de Fortify para proveedores externos, integrar el chequeo al `attemptLogin()` del login
progresivo antes de dar por buena la sesión; si se decide que 2FA es para otro caso de uso (ej.
personal interno) y no aplica a proveedores por CUIT, desactivar el feature explícitamente en vez de
dejarlo disponible sin efecto.

---

## Resueltos

### 2026-08-25 — V4: JWT en texto plano en logs

Relevamiento completo de todos los `Log::` del proyecto (54 llamadas activas en 14 archivos) a
pedido del dueño del proyecto, motivado por el tamaño de `storage/logs/laravel.log` (~75MB). Se
detectó que 13 de esas llamadas exponían el JWT completo, el CUIT o el objeto `User` entero en
`ConcursosApiService.php` (10 sitios), `ProveedorController.php` y `ConcursoController.php` (2
sitios). Se corrigió sacando esos valores de los arrays de contexto, dejando `token_length` /
`user_id` donde hacía falta para debug. Detalle completo en V4 más arriba.

Queda pendiente, como segunda etapa (no tocada en este cierre): reducir el volumen de logging de
V11 (body completo de respuestas de la API, debug de `raw_data`, el método
`testArrayToObjectRecursive` que corre en cada `GET /concursos`) — eso es ruido y peso de archivo,
no una vulnerabilidad de exposición de credenciales.
