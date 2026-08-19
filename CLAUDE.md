# CLAUDE.md — Guía de trabajo para este proyecto

**Portal de Proveedores** de Buenos Aires Energía S.A. (BAESA): la cara pública, expuesta a
internet, del sistema de gestión de proveedores y concursos de precios de BAESA. La lógica de
negocio, los datos sensibles y la verdad de proveedores/concursos viven en el **sistema interno**
(un Laravel multi-módulo separado, que tiene su propio `CLAUDE.md`). Esta app **no** es ese
sistema: es un cliente autenticado de su API JWT, más una capa mínima de login/registro/sesión
para que un proveedor externo pueda entrar sin credenciales de red interna.

El criterio de calidad acá es distinto al de una app interna: **cualquier request que llega a este
proceso puede venir de un atacante anónimo**, no de un empleado autenticado en la red de BAESA. El
objetivo es mantenerla funcionando, seguirla endureciendo progresivamente, y no perder de vista que
el radio de impacto de un bug acá no es "un módulo interno se rompe": es "un desconocido en
internet consigue algo que no debería".

El **qué** del sistema (arquitectura, flujo de datos, qué vive local vs. qué viene de la API) está
en `docs/ARQUITECTURA.md`. El estado de seguridad conocido —hallazgos, severidad, qué se corrigió—
está en `docs/VULNERABILIDADES.md`, que es un documento **vivo**: se actualiza cuando se cierra un
hallazgo o aparece uno nuevo, no se lo deja desactualizado. El contrato de la API interna que esta
app consume está documentado en `docs/API_*.md` (ya existían antes de este `CLAUDE.md`).

---

## Principio cero: nada está escrito en piedra

Ninguna decisión de este proyecto es un contrato, y menos las técnicas. Lo que sí es estable son
los axiomas de más abajo, y aun esos se cambian con una charla explícita, no de contrabando. Cuando
algo se decide o se revierte, se anota en `docs/DECISIONES.md` (bitácora append-only: una decisión
que cae se marca con una entrada nueva que la reemplaza, no se reescribe el pasado).

---

## Los modos de trabajo

Este proyecto se conversa antes de codearse. **El hilo principal —donde vos y el usuario piensan,
deciden y tocan la infraestructura transversal— es el centro.** Ahí se discute el diseño, se cierra
una decisión y se implementa todo lo que toca el núcleo sagrado: la integridad del núcleo se
protege con *presencia* (el usuario en la conversación), no con potencia. **El núcleo sagrado no se
delega.**

En este proyecto el "núcleo sagrado" no es multi-módulo como en el sistema interno: es más chico y
más filoso, porque es exactamente lo que separa "un proveedor externo entra a ver sus concursos" de
"cualquiera en internet entra a lo que no debería". Eso es: autenticación y sesión, el manejo del
JWT que habla con la API interna, cualquier cosa que decida qué datos de la API interna se muestran
a quién, y los puntos donde esta app todavía toca una base de datos interna en vez de la API (ver
axioma 2 — es deuda conocida, no un patrón a repetir).

Los agentes de `.claude/agents/` **no son rangos**: son **fases del trabajo**. Un subagente no le
sirve al usuario, le sirve al hilo principal: es una función acotada que corre en su propia ventana
de contexto y devuelve un resultado destilado, para no ensuciar la conversación con material crudo.
Se delega el trabajo *mecánico o de fan-out*, no el juicio.

- **Explorador** (`explorador` — Haiku, solo lectura). El sabueso: rastrea dónde vive una lógica,
  qué controlador arma qué vista, qué llamada a la API interna resuelve un dato, si un endpoint
  público está o no rate-limiteado — y **devuelve la conclusión con rutas exactas, no el volcado de
  archivos.** Se usa cuando contestar algo implica barrer varios archivos y solo importa el
  resultado.
- **Ejecutor** (`ejecutor` — Sonnet, edita). Ejecuta **decisiones ya tomadas** en la **periferia**:
  copy en vistas, Blade/Tailwind/Alpine, typos, un campo a `$fillable` cuando la columna ya existe,
  renombrar una variable local, mover un partial. Cumple con poco preámbulo y sin reabrir lo
  decidido. No corre tests ni commitea. Si el pedido toca auth/sesión, el manejo del JWT, una
  llamada nueva a la API interna o a una base de datos, o el núcleo sagrado en general, **corta y
  lo devuelve al hilo principal.**
- **Testeador** (`testeador` — Haiku, solo corre y reporta). Corre `php artisan test` y devuelve un
  veredicto destilado sin cargar el volcado verde en la conversación. Distingue un fallo real de la
  MySQL de XAMPP apagada. **No arregla:** un test que falla nunca se maquilla para que pase; el
  arreglo se decide en el hilo principal.

Regla que ordena todo: **planear y ejecutar el núcleo sagrado pasan por el hilo principal, con el
usuario presente.** Si dudás de si algo es núcleo o periferia acá, es núcleo: preguntá antes de
delegar — el margen de error en una app pública es menor que en una interna.

> Nota: un subagente corre en contexto aislado y **no puede preguntarte en vivo**. Por eso no se
> delega lo que va a necesitar una charla a mitad de camino.

### Cómo se pregunta

- Si algo no se entiende o hay ambigüedad, se pregunta **con opciones concretas** (A / B / C), no
  con un "¿cómo querés que lo haga?" abierto.
- Si el concepto es lo bastante grande como para que la respuesta correcta dependa de cosas que
  todavía no están decididas, no se ofrecen opciones: se pide charlarlo.
- Nunca se resuelve una ambigüedad de **diseño** o de **seguridad** eligiendo por cuenta propia y
  avisando después. Una ambigüedad de **implementación** (nombre de una variable, orden de dos
  métodos), sí: se deriva y se sigue.

---

## Cómo se trabaja

- Antes de tocar archivos, se explica qué se va a crear/modificar y por qué. Se espera confirmación
  antes de avanzar con un paso de alcance nuevo (no cada línea, sí cada salto de alcance).
- Cada paso es un cambio lógico chico, no varios cambios de golpe sin avisar.
- Si un cambio toca auth/sesión, el JWT, una llamada a la API interna, o agrega/cambia una conexión
  a una base de datos, se marca explícitamente como **efecto en cascada** antes de hacerlo. Nunca
  silencioso — ver "Axiomas de arquitectura".
- Si en el camino aparece algo necesario que no estaba pedido (un bug real, un hallazgo de
  seguridad), se hace/reporta y se explica después, con el motivo — no se pide permiso para cada
  hallazgo chico, pero tampoco se cuela sin decir nada. Un hallazgo de seguridad real se dice
  **siempre**, no se guarda para después.
- Git: commit al cerrar una etapa con sentido propio, no cada capa suelta. No se deja una etapa
  terminada sin commitear, ni se commitea a mitad de un cambio que no compila o no pasa tests. Antes
  de tocar archivos con cambios sin commitear, se revisa `git status` / `git diff`. Mensajes en
  español, estilo del historial del repo.

---

## Axiomas de arquitectura

Son las reglas que no se negocian sin una conversación explícita. Todo lo demás es táctica.

1. **Esta app es la superficie de ataque real del sistema BAESA.** Es la única pieza publicada a
   internet libre; el sistema interno y sus bases nunca deberían ser alcanzables directamente desde
   afuera. Todo lo que se decida acá parte de asumir que cualquier request puede ser hostil: no hay
   "usuario de confianza" implícito como en una intranet.

2. **Los datos de negocio (proveedores, concursos, documentos) no viven acá.** Viven en el sistema
   interno y se leen/escriben **únicamente** vía su API JWT (`PLATAFORMA_API_URL`), a través de
   `App\Services\ConcursosApiService` y `App\Services\ProveedorApiService`. Esta app solo persiste
   localmente lo que Laravel/Jetstream necesita para autenticar: `users`, `login_attempts`,
   `password_reset_tokens`, sesiones.
   **Excepción heredada y a cerrar, no a imitar:** `ProgressiveLoginController::checkCuitForRegistration`,
   `ProviderRegistrationController::checkCuit` y tres puntos de `app/Livewire/Auth/ProgressiveLogin.php`
   todavía consultan la base `proveedores` por conexión Eloquent directa (`Proveedor::on('proveedores')`)
   en vez de usar `POST /api/validate-provider`, que ya está documentado en
   `docs/API_PROVEEDORES_CONCURSOS.md` y no se usa. Ver `docs/VULNERABILIDADES.md` (V2). No se agregan
   usos nuevos de las conexiones `proveedores`/`concursos`: si se toca ese código, se migra a la API.

3. **El JWT que emite el sistema interno es el activo más sensible que pasa por este proceso.**
   Nunca se loguea su valor (ni completo ni parcial — a lo sumo su longitud), se guarda solo en
   `session('jwt_token')`, y `JWT_SECRET` (necesario porque `RefreshJwtToken` decodifica el token
   localmente para chequear `exp`) se trata con el mismo cuidado que una clave privada: si esta app
   se compromete, ese secreto compromete la confianza de la API interna en **cualquier** token que
   firme. No se agrega ningún código nuevo que loguee o exponga el token.

4. **Mass assignment cerrado.** Todo modelo declara `$fillable` explícito, nunca `$guarded = []` ni
   `$guarded = false`. (Deuda existente: `User::$guarded = false` — no se agrega ningún
   `create()`/`fill()`/`update()` con `$request->all()` mientras eso no se corrija; ver
   `docs/VULNERABILIDADES.md` V5.)

5. **Rate limiting + bloqueo de cuenta son la única defensa contra fuerza bruta que existe hoy.**
   Login, `checkUser` y `checkCuitForRegistration` están limitados por IP y el usuario se bloquea
   15 minutos a los 5 intentos fallidos. Cualquier endpoint público nuevo que toque credenciales,
   CUITs o tokens se suma a esa disciplina, no la elude ni la duplica con su propia lógica paralela.

6. **Sin SPA.** Server-rendered vía Blade/Livewire 3 + Alpine.js puntual, sesión de
   Jetstream/Fortify/Sanctum. No se introduce un framework front nuevo sin pasar por el Principio
   cero.

7. **El dominio se nombra en castellano.** Modelos, variables, rutas y vistas siguen el glosario del
   negocio en español.

8. **Nunca `dd()`, `dump()`, `var_dump()` ni `console.log` de debug en código commiteado — ni
   siquiera en una ruta que hoy está comentada/inalcanzable.** Una ruta muerta hoy puede
   descomentarse mañana sin que nadie revise el método detrás. (Deuda existente:
   `CustomLoginController::login` tiene un `dd($request->all())`; ver `docs/VULNERABILIDADES.md`
   V6.)

---

## Stack y decisiones tomadas

- **Backend**: Laravel 11, PHP 8.2.
- **Frontend**: Livewire 3 + Blade + Alpine.js + TailwindCSS, compilado con Vite.
- **Auth**: Laravel Jetstream + Fortify (registro estándar deshabilitado — hay un flujo de "login
  progresivo" a medida). Sanctum para la sesión. 2FA disponible vía Fortify.
- **DB local**: una sola base MySQL (`portalproveedores` / `DB_DATABASE_LOCAL`) con lo que Laravel
  necesita más `login_attempts`. **No** es multi-DB por diseño — a diferencia del sistema interno,
  acá multi-DB es deuda a cerrar, no un patrón (ver axioma 2).
- **API interna**: cliente HTTP con JWT (`firebase/php-jwt`) contra `PLATAFORMA_API_URL`. Ver
  `routes/api.php` (nota: ese archivo es casi vacío — la API que consumimos vive en el sistema
  interno, no la exponemos nosotros).
- **Email**: `innoge/laravel-msgraph-mail` (MS Graph) para contraseñas temporales y reset.

---

## Entorno y comandos

- **SO / shell**: Windows + XAMPP, shell **PowerShell**. La DB es la **MySQL de XAMPP** — tiene que
  estar levantada para correr la app y los tests.
- **Tests**: `php artisan test` (todo) o `php artisan test --filter=<Nombre>` para acotar. Acá los
  tests usan `RefreshDatabase` (no `DatabaseTransactions`) — no hay multi-base que preservar, así
  que recrear el esquema en cada test es lo simple y correcto.
- **Formateo**: `./vendor/bin/pint`. Correr sobre lo tocado antes de commitear.
- **Assets**: `npm run dev` (watch) / `npm run build` (producción).
- **Variables de entorno propias del proyecto** (no están en `.env.example`, que quedó
  desactualizado — ver `docs/ARQUITECTURA.md`): `PLATAFORMA_API_URL`, `JWT_SECRET`,
  `DB_DATABASE_LOCAL`/`DB_DATABASE_PROVEEDORES`/`DB_DATABASE_CONCURSOS` y sus credenciales,
  `MAIL_MICROSOFT_GRAPH_*`. Si se toca `.env.example`, es un cambio de periferia (documentación de
  config), no de núcleo.

---

## Testing

No hay una suite histórica pensada para esta app específicamente (los tests actuales son en buena
parte los que trae Jetstream de fábrica). El criterio se dosifica por zona y checkpoint, igual que
en el sistema interno.

**Núcleo sagrado acá (auth/sesión, manejo del JWT, llamadas a la API interna, cualquier acceso a
`proveedores`/`concursos`):**

- Todo cambio en este núcleo lleva test antes de considerarse terminado: camino feliz + el caso
  malicioso obvio (credenciales incorrectas, token vencido, rate limit alcanzado, CUIT ajeno).
- Nombres en español descriptivo: `un_usuario_bloqueado_no_puede_reintentar_login`.

**Periferia (vistas, copy, Tailwind):**

- Lógica cerrada y trivial no lleva test propio.

**Alcance de la corrida:** durante el trabajo, solo el test relevante (`--filter=<Nombre>`). La
suite completa es evento de checkpoint (cerrar un bloque grande, antes de commitear algo del núcleo
sagrado). Se reporta el resumen, no el volcado — por eso la corrida va por el `testeador`.

**Testeo visual**: no lo hace Claude, salvo pedido explícito. La revisión visual la hace el usuario.

---

## Principios de código

- **YAGNI explícito**: sin capas de abstracción hasta que una funcionalidad concreta las necesite.
- Convenciones estándar de Laravel/Livewire/Jetstream. Sin inventar estructura de carpetas propia.
- Validación en los bordes del sistema (input de usuario, Form Requests, respuesta de la API
  interna), no en cada método interno que ya confía en sus invariantes.
- **Tres líneas repetidas son mejores que una abstracción prematura.**
- `$fillable` explícito, nunca `$guarded = []` ni `$guarded = false` (ver axioma 4).
- Español en nombres, métodos, mensajes de validación y comentarios. camelCase (métodos/variables),
  PascalCase (clases), snake_case (tablas/columnas).
- **Nunca se loguea un JWT, una contraseña, ni el body crudo de una respuesta de la API interna que
  contenga datos personales de un proveedor** (email, teléfono, dirección) salvo que sea
  estrictamente necesario para debuggear un incidente puntual, y en ese caso se saca del código
  antes de cerrar la tarea — no se deja como logging permanente "por si sirve después".

---

## Documentación

- `docs/ARQUITECTURA.md` — el **qué** de esta app: flujo de datos, qué vive local vs. en la API
  interna, inventario de lo que quedó como deuda (conexiones directas, código muerto).
- `docs/VULNERABILIDADES.md` — hallazgos de seguridad conocidos con severidad y estado. **Vivo**: se
  actualiza al cerrar o descubrir un hallazgo, no se lo deja como foto vieja.
- `docs/API_*.md` y `docs/RESUMEN_ENDPOINTS_NUEVOS.md` — el contrato de la API interna que esta app
  consume. Cambia solo cuando cambia el lado interno; si se toca, es núcleo sagrado (axioma 2) y se
  avisa como tal.
- `docs/DECISIONES.md` — bitácora append-only de decisiones de diseño/arquitectura.
- `docs/updates/YYYY-MM-DD_titulo.md` — detalle de cambios significativos (ya hay precedente).

---

## Lo que no hacer

- No agregar usos nuevos de las conexiones `proveedores`/`concursos`: todo dato de negocio nuevo se
  pide por la API interna.
- No loguear un JWT, ni completo ni parcial.
- No usar `$guarded = []` ni `$guarded = false`. No dejar `dd()`, `dump()`, `var_dump()` ni
  `console.log` de debug en commits, tampoco en rutas comentadas.
- No introducir Vue/React ni un framework front nuevo sin pasar por el Principio cero.
- No crear abstracciones (repositorios/servicios) ni campos "por si acaso" sin necesidad real.
- No hacer lazy-loading en vistas. No mezclar inglés con castellano en el dominio.
- No asumir que un endpoint público está protegido porque "nadie lo va a encontrar" — todo endpoint
  público se trata como si un atacante ya lo conociera.
