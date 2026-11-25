# Decisiones — Portal de Proveedores (app externa)

Bitácora append-only. Una decisión que cae se marca con una entrada nueva que la reemplaza; no se
reescribe el pasado.

---

## 2026-08-18 — Se adopta un `CLAUDE.md` propio para esta app, separado del sistema interno

**Contexto:** este repo venía con el `CLAUDE.md` y los agentes (`.claude/agents/`) copiados tal
cual del sistema interno de BAESA (multi-módulo, multi-DB por módulo, permisos Spatie
`Modulo/Rol`, MediaLibrary, 12 módulos). Al retomar el proyecto se verificó que ese documento no
describe esta app: es un cliente chico de la API del interno, con una sola base de datos local y un
stack más acotado (sin `spatie/laravel-permission`, sin MediaLibrary, sin multi-módulo).

**Decisión:** se reescribió `CLAUDE.md` con axiomas propios de esta app (ver el archivo), se
crearon `docs/ARQUITECTURA.md` y `docs/VULNERABILIDADES.md`, y se ajustaron los tres agentes de
`.claude/agents/` para que dejen de asumir la arquitectura del sistema interno.

**Hallazgo relevante que motivó parte de la revisión:** durante la exploración se confirmó que esta
app todavía tiene conexiones SQL directas y credenciales propias a las bases internas `proveedores`
y `concursos` (no solo acceso vía API), heredadas de una refactorización incompleta. Ver
`docs/VULNERABILIDADES.md` V2.

---

## 2026-10-27 — Formulario público de registro: mail solo interno + Cloudflare Turnstile

**Contexto:** bots enviaban el formulario de `/bienvenido` y pasaban cada filtro que se agregaba.
Al analizarlo apareció el motivo: el mail se mandaba también a la dirección que escribía el
visitante, con su texto libre, así que el formulario servía para mandar spam gratis con el
dominio de BAESA (`docs/VULNERABILIDADES.md` V14).

**Decisiones:**
- **El mail va solo a `infoproveedores@`.** Al solicitante no se le manda ninguna confirmación por
  mail. Así desaparece lo que hacía valioso al formulario para un spammer.
- **Validación del lado del servidor** de todos los campos. Nunca más `$request->all()` al mail.
- **Cloudflare Turnstile como captcha.** Se evaluaron opciones sin dependencias externas (ALTCHA
  con proof-of-work, trampas de tiempo, honeypot mejorado, captcha de imagen local). Se eligió
  Turnstile porque usa señales de reputación de toda la red de Cloudflare, cosa que una solución
  alojada en el propio servidor no tiene. Es la primera dependencia externa en el camino de un
  formulario público.
- **Fail-closed:** si Cloudflare no responde, el envío se rechaza con un mensaje para reintentar.
  Se prefirió frenar a algún proveedor legítimo durante una caída rara antes que abrir una ventana
  a los bots.
- **Rate limit postergado** (`docs/VULNERABILIDADES.md` V15). Queda en tensión con el axioma 5, y
  se anota como pendiente explícito.

---

## 2026-11-20 — Renovación del JWT: reactiva ante un 401, la preventiva se posterga

**Contexto:** un proveedor no pudo presentar oferta en el concurso 145 antes del cierre. Su JWT de
10 minutos había vencido, y desde `b0a60b1` (abril) `RefreshJwtToken` no estaba registrado, así que
nada lo renovaba. El modal no mostraba el error. Ver `docs/VULNERABILIDADES.md` V16.

**Decisiones:**
- **Renovación reactiva en `ConcursosApiService`:** ante un 401, se pide un token nuevo (o se usa uno
  ya renovado en la sesión) y se reintenta una sola vez. Si falla, se devuelve el 401 original, así
  que el peor caso es igual que antes. Se hizo primero porque no cambia nada en el camino que
  funciona y se puede subir en plena temporada de concursos.
- **La renovación preventiva (volver a registrar `RefreshJwtToken`) se posterga.** Toca todos los
  requests y hay que probarla con calma. Al retomarla, conviene rehacerla sin decodificar el token,
  para que la app pueda dejar de tener `JWT_SECRET` (V1).
- **Sin cambios en el front:** las vistas se están actualizando en otro lado. Mostrar el error de
  intención en el modal queda pendiente para cuando se integre ese trabajo. Por ahora los logs de
  error de la API suman `user_id` y `concurso_id`, para poder reconstruir un incidente.

---

## 2026-11-25 — Formulario de un día para la 2° Ronda de Negocios (`/formularioprovincia`)

**Contexto:** BAESA y BAGSA comparten stand en la "2° Ronda de Negocios de Energía y Minería" de la
Provincia de Buenos Aires. Los proveedores llegan por un QR impreso en una lona. La maqueta original
está en `docs/form/index.html`.

**Decisiones:**
- **Se guarda local, como JSON,** en `registros_formulario_provincia.datos`. No es dato de negocio
  de la API interna (axioma 2): es una lista de contactos de un evento que se lee directo de la base
  cuando termina. La forma de exportarla se decide después.
- **Sin correo y sin campos obligatorios.** Se aceptan filas incompletas o vacías.
- **2 envíos sobre 1 fila:** el Paso 1 crea la fila y guarda su id en la sesión. El Paso 2
  (opcional) actualiza esa fila y toma el id solo de la sesión, nunca del request.
- **Sin rate limit, honeypot ni Turnstile,** por decisión explícita del dueño del proyecto: dura un
  día, y en el evento mucha gente comparte IP. Queda en tensión con el axioma 5 y se anota como
  V18 en `docs/VULNERABILIDADES.md`.
- **Se apaga con `FORMULARIO_PROVINCIA_ACTIVO=false`** (404). El valor por defecto es apagado.
- **Rubros fijos en el JS,** sin las tablas `categorias_rubro`/`rubros` que proponía la maqueta (YAGNI).
