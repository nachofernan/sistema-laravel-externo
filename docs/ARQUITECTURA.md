# Arquitectura — Portal de Proveedores (app externa)

Última revisión: 2026-08-18.

## Qué es esta app

La cara pública del sistema de proveedores/concursos de BAESA. Un proveedor externo entra acá con
CUIT + contraseña, ve sus concursos, sube documentación y gestiona su participación. Ningún dato de
negocio (proveedor, concurso, documento) se origina ni se guarda de verdad acá: todo viene del
**sistema interno** vía una API JWT. Esta app es, en esencia, un cliente HTTP de esa API con una
capa de login/sesión propia encima.

```
Navegador (proveedor externo)
      │  HTTPS
      ▼
┌─────────────────────────────────────────────┐
│  Este repo (Laravel 11 + Livewire 3)         │
│  - Auth propia (CUIT + password, Jetstream)  │
│  - Guarda JWT en session('jwt_token')        │
│  - Base local: solo users/login_attempts/... │
└───────────────┬───────────────────────────────┘
                │  HTTP + Bearer JWT (PLATAFORMA_API_URL)
                ▼
┌─────────────────────────────────────────────┐
│  Sistema interno de BAESA (otro Laravel)     │
│  - Bases: usuarios, proveedores, concursos...│
│  - Dueño real de los datos de negocio        │
└─────────────────────────────────────────────┘
```

## Lo que persiste localmente

Una sola base MySQL (`DB_DATABASE_LOCAL`, por defecto `portalproveedores`), con:

- `users` — cuenta del proveedor en el portal externo (no confundir con el usuario interno de
  BAESA). Columnas propias agregadas sobre el esquema de Jetstream: `status`, `last_login_at`,
  `failed_login_attempts`, `locked_until`, `registration_ip`, `last_login_ip`,
  `must_change_password` (migraciones `2024_10_09_*` y `2025_06_24_072442_add_data_to_users_table`).
- `login_attempts` (`app/Models/LoginAttempt.php`) — auditoría de intentos de login/registro/check,
  con IP, user agent y resultado. Es la base del rate limiting a nivel de cuenta.
- `password_reset_tokens` — tokens de recuperación propios (no el mecanismo estándar de Laravel:
  ver `PasswordResetController`).
- Tablas de Jetstream/Sanctum/Fortify de fábrica (sessions, personal_access_tokens, etc.).

Eso es todo lo que esta app "es dueña" de verdad.

## La API interna que consume

Documentada en `docs/API_CONCURSOS_COMPLETA.md`, `docs/API_PROVEEDORES_CONCURSOS.md`,
`docs/API_DOCUMENTOS_ADICIONALES.md` y `docs/RESUMEN_ENDPOINTS_NUEVOS.md`. Resumen del contrato:

- Auth: `POST /api/generate-token` con `{cuit, email}` devuelve un JWT (HS256, `JWT_SECRET`
  compartido), válido ~10 minutos.
- Todos los endpoints de negocio (`/api/proveedores/{cuit}`, `/api/concursos`,
  `/api/concursos/{id}/documentos`, etc.) requieren `Authorization: Bearer <token>`.
- El cliente de esta app son dos clases en `app/Services/`:
  - `ConcursosApiService` — concursos, invitaciones, documentos de concurso.
  - `ProveedorApiService` — datos del proveedor, documentos, apoderados; tiene lógica de
    auto-refresh de token en `getProveedor()` (reintenta una vez tras un 401).
- `app/Http/Controllers/AuthController::getNewToken()` es el único punto que le pide un token nuevo
  a la API interna; `app/Http/Middleware/RefreshJwtToken.php` lo llama proactivamente cuando faltan
  menos de 2 minutos para el `exp` del token en sesión (decodificándolo localmente con
  `JWT_SECRET` — ver `docs/VULNERABILIDADES.md` V1 sobre la implicancia de esto).

`routes/api.php` de este repo está casi vacío (solo `/api/user` de Sanctum). Es decir: **esta app
consume la API, no la expone.** Si alguna vez se necesita que la app externa exponga su propia API,
eso es un cambio de núcleo sagrado nuevo, no una extensión de lo que ya existe.

## Flujo de login

Sistema "login progresivo" a medida (no el login de Fortify de fábrica, que está reducido a
password reset / 2FA / update password — el registro estándar de Fortify está deshabilitado en
`config/fortify.php`):

1. `POST /auth/check-user` (`ProgressiveLoginController::checkUser`) — confirma si el CUIT tiene
   cuenta activa en `users`. Rate limit: 10/15min por IP.
2. `POST /auth/login` (`ProgressiveLoginController::login`) — valida contra `Hash::check`, bloquea
   la cuenta 15 min a los 5 intentos fallidos, y si es exitoso pide un JWT a la API interna y lo
   guarda en `session('jwt_token')`. Rate limit: 5/15min por IP.
3. `POST /auth/check-registration` (`ProgressiveLoginController::checkCuitForRegistration`) — si el
   CUIT no tiene cuenta pero **sí** es un proveedor conocido con `portal_access_granted` en la base
   interna, crea el `User` local con contraseña temporal y la manda por mail. Este es uno de los
   puntos que consulta la base `proveedores` por conexión directa en vez de la API (ver más abajo).

Hay una implementación paralela de este mismo flujo en `app/Livewire/Auth/ProgressiveLogin.php`
(componente Livewire con su propio rate limiting y su propio log del JWT), pero no está montada en
ninguna vista (`grep` sobre `resources/views` no encuentra `<livewire:auth.progressive-login`) — es
código muerto que duplica la lógica de seguridad del controller activo. Ver
`docs/VULNERABILIDADES.md` (sección informativos).

`CustomLoginController` es un login más viejo, con sus rutas comentadas en `routes/web.php` salvo
`logout` y `validate-session`. Su método `login()` tiene un `dd($request->all())` sin sacar — hoy
inalcanzable porque la ruta está comentada, pero queda ahí como código listo para reactivarse mal si
alguien descomenta el bloque "legacy" sin revisar.

## La excepción de aislamiento: conexiones directas a `proveedores` y `concursos`

`config/database.php` define conexiones MySQL completas a `proveedores` y `concursos` (con
credenciales propias: `DB_USERNAME_PROVEEDORES`, `DB_PASSWORD_CONCURSOS`, etc.), y el repo todavía
tiene ~20 modelos Eloquent con `protected $connection = 'proveedores'` o `'concursos'` en
`app/Models/Proveedores/` y `app/Models/Concursos/` (`Proveedor`, `Concurso`, `Invitacion`,
`Documento`, `Bancario`, `Contacto`, `Rubro`, etc. — herencia de cuando esta app hablaba directo con
esas bases, antes de la migración a la API documentada en `docs/API_CONCURSOS_IMPLEMENTACION.md`).

De todo ese árbol de modelos, el **único uso real hoy** es `Proveedor::on('proveedores')->where('cuit', ...)`,
en 4 lugares:

- `app/Http/Controllers/Auth/ProgressiveLoginController.php:196`
- `app/Http/Controllers/Auth/ProviderRegistrationController.php:37` (controller sin rutas activas)
- `app/Livewire/Auth/ProgressiveLogin.php:97,174,245` (componente sin montar)

Es decir: en producción, el único call site vivo es el de `ProgressiveLoginController`, usado para
chequear si un CUIT es proveedor conocido antes de ofrecer el registro. La API interna ya expone
`POST /api/validate-provider` con exactamente ese propósito (`docs/API_PROVEEDORES_CONCURSOS.md`
§2.2) y no se está usando.

El resto de los modelos de `Proveedores/` y `Concursos/` (documentos, apoderados, bancarios,
contactos, direcciones, rubros, invitaciones, prórrogas, sedes...) no tiene ningún call site fuera
de sí mismos: es código muerto que **however** mantiene vivas las credenciales y la superficie de
conexión a esas dos bases internas. Ver `docs/VULNERABILIDADES.md` V2 para el impacto de seguridad
de esto.

## Subida/descarga de archivos

Todo pasa por la API interna (`ConcursosApiService`/`ProveedorApiService` con `Http::attach(...)`),
no hay almacenamiento local de los documentos de proveedores/concursos en esta app. Validación
antes de reenviar: tamaño (`max:10240` = 10MB), sanitización de nombre
(`FileController::sanitizeFileName`, regex a `[a-zA-Z0-9._-]`), y un chequeo de header `%PDF` para
los flujos que exigen PDF (`isValidPdfFile`) — no hay validación de tipo MIME real para el resto de
los formatos que la API dice soportar (DOC, XLS, JPG, PNG).

## Cosas a revisar / deuda que no es responsabilidad exclusiva del código

- **`.env.example` está desactualizado**: no lista `PLATAFORMA_API_URL`, `JWT_SECRET`,
  `DB_DATABASE_LOCAL`/`_PROVEEDORES`/`_CONCURSOS` y sus credenciales, ni `MAIL_MICROSOFT_GRAPH_*`.
  Un clone nuevo siguiendo el `.env.example` tal cual no levanta. No es una vulnerabilidad, pero es
  fricción operativa real.
- **Verificar en el servidor real (no en este `.env` local)** que `APP_ENV=production` y
  `APP_DEBUG=false`. El `.env` de este working directory tiene `APP_ENV=local` /
  `APP_DEBUG=true`, lo cual es razonable para un entorno de desarrollo local pero sería crítico si
  se replica en el deployment público — ver `docs/VULNERABILIDADES.md` V3.
- No hay `docs/modulos/` ni estructura multi-módulo: a diferencia del sistema interno, este repo es
  chico y de una sola pieza, así que no se replica esa estructura de documentación acá.
- **Dos problemas de entorno de test, preexistentes, detectados el 2026-08-25 al agregar
  `tests/Feature/EventoUsuarioTest.php`** (no son bugs del código de `eventos_usuario`, que se
  verificó manualmente y con el test de `suspend()` pasando en limpio):
  - **Cualquier test que hace una request HTTP real (`$this->get()`/`$this->post()`) devuelve 404.**
    `APP_URL` en `.env` incluye el subdirectorio (`http://172.17.9.231/portalproveedores/public`), y
    Laravel usa `config('app.url')` como base URL para las requests simuladas en tests. Como las
    rutas están declaradas sin ese prefijo (`Route::get('/login', ...)`), la request termina
    pidiendo `/portalproveedores/public/login`, que no matchea ninguna ruta. Confirmado que esto
    afecta también tests preexistentes no tocados hoy (`AuthenticationTest::login screen can be
    rendered` falla igual). Se necesitaría un `.env.testing` con `APP_URL=http://localhost` (o
    similar) para que los tests HTTP funcionen.
  - **Cualquier test que use `Livewire::test(...)` falla con
    `ErrorException: Trying to access array offset on value of type null` en
    `vendor/livewire/livewire/.../HandleComponents.php:88`** (snapshot null). Ya se había visto este
    mismo error en tests no relacionados en una sesión anterior. No investigado a fondo — queda como
    deuda de entorno de testing, no de la lógica de la app.
