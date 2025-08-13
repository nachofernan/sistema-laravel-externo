# Nuevos Endpoints de Concursos - Eliminación de Documentos y Baja de Ofertas

## Resumen de Implementación

Se han agregado dos nuevos endpoints al controlador de concursos para permitir a los proveedores gestionar sus ofertas:

### 1. Eliminar Documento de Oferta

**Endpoint:** `DELETE /api/concursos/{concurso_id}/documentos/{documento_id}`

**Funcionalidad:**
- Permite eliminar un documento específico de la oferta del proveedor
- Solo funciona si el concurso no ha cerrado (fecha actual < fecha_cierre)
- Solo permite eliminar documentos subidos por el proveedor (no por la empresa)
- Elimina tanto el registro de la base de datos como el archivo físico

**Validaciones de Seguridad:**
- Verifica que el proveedor tenga acceso al concurso mediante la invitación
- Verifica que la fecha actual sea anterior a la fecha de cierre del concurso
- Verifica que el documento pertenezca a la oferta del proveedor
- Verifica que el documento haya sido subido por el proveedor (user_id_created = null)

**Respuestas:**
- `200`: Documento eliminado correctamente
- `403`: Concurso cerrado o documento de empresa
- `404`: Documento no encontrado
- `500`: Error interno del servidor

### 2. Dar de Baja Oferta Completa

**Endpoint:** `DELETE /api/concursos/{concurso_id}/oferta`

**Funcionalidad:**
- Elimina todos los documentos subidos por el proveedor en la oferta
- Cambia la intención de participación a 1 (con intención)
- Solo funciona si el concurso no ha cerrado
- No elimina documentos ingresados por la empresa

**Validaciones de Seguridad:**
- Verifica que el proveedor tenga acceso al concurso mediante la invitación
- Verifica que la fecha actual sea anterior a la fecha de cierre del concurso
- Solo elimina documentos donde user_id_created = null (subidos por proveedor)

**Respuestas:**
- `200`: Oferta dada de baja correctamente con contador de documentos eliminados
- `403`: Concurso cerrado
- `500`: Error interno del servidor

## Archivos Modificados

### 1. Controlador
- **Archivo:** `app/Http/Controllers/API/ConcursoController.php`
- **Métodos agregados:**
  - `eliminarDocumento()`: Elimina un documento específico
  - `darBajaOferta()`: Da de baja la oferta completa

### 2. Rutas
- **Archivo:** `routes/api.php`
- **Rutas agregadas:**
  - `DELETE /concursos/{concurso_id}/documentos/{documento_id}`
  - `DELETE /concursos/{concurso_id}/oferta`

### 3. Documentación
- **Archivo:** `docs/API_CONCURSOS_COMPLETA.md`
- **Secciones agregadas:**
  - Documentación completa de ambos endpoints
  - Ejemplos de uso con curl
  - Códigos de respuesta y manejo de errores

### 4. Pruebas
- **Archivo:** `tests/Feature/Concursos/ConcursoControllerTest.php`
- **Pruebas implementadas:**
  - Eliminación exitosa de documentos
  - Validación de fecha de cierre
  - Validación de documentos de empresa
  - Baja completa de oferta
  - Validaciones de seguridad

## Características de Seguridad

### Validación de Fechas
- Ambos endpoints verifican que la fecha actual sea anterior a la fecha de cierre del concurso
- Utiliza `now()->gte($concurso->fecha_cierre)` para la comparación

### Validación de Propiedad
- Solo permite eliminar documentos que pertenezcan a la oferta del proveedor
- Verifica la relación entre invitación, concurso y proveedor

### Validación de Origen
- Solo permite eliminar documentos subidos por el proveedor (user_id_created = null)
- Protege documentos ingresados por usuarios de la empresa

### Transacciones de Base de Datos
- Ambos endpoints utilizan transacciones para garantizar consistencia
- Rollback automático en caso de error

## Ejemplos de Uso

### Eliminar Documento
```bash
curl -X DELETE \
     -H "Authorization: Bearer <token>" \
     https://api.ejemplo.com/api/concursos/1/documentos/5
```

### Dar de Baja Oferta
```bash
curl -X DELETE \
     -H "Authorization: Bearer <token>" \
     https://api.ejemplo.com/api/concursos/1/oferta
```

## Consideraciones Técnicas

### Spatie Media Library
- Los archivos se eliminan usando el método `delete()` de Spatie Media Library
- Se verifica la existencia del media antes de intentar eliminarlo

### Base de Datos
- Se utiliza la conexión 'concursos' para todas las operaciones
- Las transacciones garantizan la integridad de los datos

### Respuestas JSON
- Todas las respuestas siguen el formato estándar de la API
- Incluyen códigos de éxito/error y mensajes descriptivos

## Próximos Pasos

1. **Ejecutar pruebas:** Verificar que todas las pruebas pasen correctamente
2. **Testing manual:** Probar los endpoints con datos reales
3. **Documentación frontend:** Actualizar la documentación para desarrolladores frontend
4. **Monitoreo:** Implementar logging para monitorear el uso de estos endpoints 