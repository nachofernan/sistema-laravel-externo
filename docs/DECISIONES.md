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
