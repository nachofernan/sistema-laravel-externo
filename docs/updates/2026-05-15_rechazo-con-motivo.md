# Rechazo de concurso con motivo de no participación

**Fecha:** 2026-05-15  
**Rama:** main  
**Archivos modificados:** 3

---

## Contexto

La API interna actualizó el endpoint `PATCH /api/concursos/{concurso_id}/invitacion` para aceptar un campo opcional `observaciones` cuando el proveedor indica que no participará (`intencion: 2`). Ver especificación completa en `docs/2026-05-15_instrucciones-app-externa-rechazo.txt`.

---

## Cambios realizados

### `app/Services/ConcursosApiService.php`

- `cambiarIntencion()` acepta ahora un tercer parámetro opcional `?string $observaciones = null`.
- Cuando `intencion === 2` y `$observaciones` no está vacío, se incluye en el payload del PATCH.
- Para cualquier otra intención el campo se omite (el backend lo limpia automáticamente).

### `app/Livewire/Concursos/ActionModal.php`

Nuevas propiedades públicas:

| Propiedad | Tipo | Uso |
|---|---|---|
| `$mostrando_motivo` | `bool` | Controla la visibilidad del panel de selección de motivo |
| `$motivo_seleccionado` | `string` | Valor del radio seleccionado (`'No quiero'`, `'No tengo ganas'`, `'No tengo stock'`, `'otro'`) |
| `$motivo_texto` | `string` | Texto libre cuando se elige "Otro" |

Nuevos métodos:

- **`iniciarRechazo()`** — muestra el panel de motivo y resetea el estado anterior. Es llamado por los botones "No participaré" en estados `pendiente` y `listo_*`.
- **`confirmarRechazo()`** — valida la selección; construye `observaciones` como el label del radio o `"Otros: {texto}"` si eligió "Otro"; llama al borrado de archivos previos (`darBajaOferta`) y luego a `cambiarIntencion(id, 2, $observaciones)`.

Cambio en `updateIntention()`: el borrado de archivos al rechazar fue movido a `confirmarRechazo()`, dejando `updateIntention()` solo para intenciones 1 y 3.

### `resources/views/livewire/concursos/action-modal.blade.php`

- Los dos botones "No participaré" (en estados `pendiente` y `listo_*`) ahora llaman a `iniciarRechazo` en lugar de `updateIntention(2)` directamente.
- Se agrega el **panel de motivo de rechazo** con 4 radio buttons:
  - "No quiero"
  - "No tengo ganas"
  - "No tengo stock"
  - "Otro" → despliega un `<textarea>` (máx. 950 caracteres)
- El panel se oculta con `$mostrando_motivo`; mientras está visible se suprimen los bloques normales de `pendiente` y `listo_*` para evitar superposición.
- **Estado `rechazado`**: muestra el motivo guardado (`$invitacion->observaciones`) si existe, junto con el texto "En caso de querer revertir esta decisión, haga clic en el botón de abajo."

---

## Opciones de motivo y valor enviado a la API

| Opción mostrada | Valor enviado en `observaciones` |
|---|---|
| No quiero | `No quiero` |
| No tengo ganas | `No tengo ganas` |
| No tengo stock | `No tengo stock` |
| Otro (+ texto) | `Otros: {texto ingresado}` |

Si no se selecciona ningún motivo y el usuario igual intenta confirmar, se muestra un error de validación inline.

---

## Notas

- El campo `observaciones` es opcional en el backend; si no viene o viene vacío, se guarda como `null` sin error.
- Los textos de las opciones están hardcodeados en la vista (sin endpoint de catálogo, según la especificación).
- La lista de opciones puede extenderse en futuras versiones de la app sin requerir cambios en el backend.
