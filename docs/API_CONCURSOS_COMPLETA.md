# API de Concursos - Documentación Completa

Este documento describe la API RESTful para la gestión de concursos por parte de proveedores externos. La API permite a los proveedores ver concursos a los que fueron invitados, gestionar su participación y subir/descargar documentación.

---

## 1. Autenticación

- **Tipo:** JWT (JSON Web Token)
- **Header requerido:** `Authorization: Bearer <token>`
- **Obtención del token:** `POST /api/generate-token`

---

## 2. Endpoints de Concursos

Todos los endpoints requieren autenticación JWT válida.

### 2.1. Listar Concursos del Proveedor

**GET** `/api/concursos`

Obtiene todos los concursos donde el proveedor autenticado fue invitado.

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Concurso de Servicios Informáticos",
            "numero": 2024001,
            "descripcion": "Servicios de mantenimiento y soporte informático",
            "fecha_inicio": "2024-01-15 09:00:00",
            "fecha_cierre": "2024-02-15 18:00:00",
            "numero_legajo": "LEG-2024-001",
            "legajo": "2024-001",
            "estado": {
                "id": 2,
                "nombre": "Activado",
                "estado_actual": "activo"
            },
            "subrubro": {
                "id": 5,
                "nombre": "Servicios Informáticos",
                "rubro": {
                    "id": 2,
                    "nombre": "Tecnología"
                }
            },
            "documentos_requeridos": [
                {
                    "id": 1,
                    "nombre": "Pliegos de Bases y Condiciones Generales",
                    "descripcion": "Documento base del concurso",
                    "de_concurso": true,
                    "encriptado": false,
                    "obligatorio": true
                }
            ],
            "invitacion": {
                "id": 10,
                "concurso_id": 1,
                "proveedor_id": 123,
                "intencion": 0,
                "fecha_envio": "2024-01-10"
            },
            "created_at": "2024-01-10 10:00:00",
            "updated_at": "2024-01-10 10:00:00"
        }
    ],
    "message": "Concursos obtenidos correctamente."
}
```

### 2.2. Obtener Detalles de un Concurso

**GET** `/api/concursos/{concurso_id}`

Obtiene información completa de un concurso específico, incluyendo documentos, contactos, sedes y prórrogas.

**Parámetros:**
- `concurso_id` (integer): ID del concurso

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Concurso de Servicios Informáticos",
        "numero": 2024001,
        "descripcion": "Servicios de mantenimiento y soporte informático",
        "fecha_inicio": "2024-01-15 09:00:00",
        "fecha_cierre": "2024-02-15 18:00:00",
        "numero_legajo": "LEG-2024-001",
        "legajo": "2024-001",
        "estado": {
            "id": 2,
            "nombre": "Activado",
            "estado_actual": "activo"
        },
        "subrubro": {
            "id": 5,
            "nombre": "Servicios Informáticos",
            "rubro": {
                "id": 2,
                "nombre": "Tecnología"
            }
        },
        "contactos": [
            {
                "id": 1,
                "concurso_id": 1,
                "tipo": "Técnico",
                "nombre": "Ing. Juan Pérez",
                "email": "juan.perez@empresa.com",
                "telefono": "011-1234-5678"
            }
        ],
        "sedes": [
            {
                "id": 1,
                "nombre": "Sede Central",
                "direccion": "Av. Corrientes 1234",
                "ciudad": "Buenos Aires",
                "provincia": "CABA",
                "codigo_postal": "1043",
                "telefono": "011-1234-5678",
                "email": "central@empresa.com"
            }
        ],
        "prorrogas": [
            {
                "id": 1,
                "concurso_id": 1,
                "fecha_anterior": "2024-02-15 18:00:00",
                "fecha_nueva": "2024-02-20 18:00:00",
                "motivo": "Extensión por feriado nacional"
            }
        ],
        "documentos": [
            {
                "id": 1,
                "concurso_id": 1,
                "invitacion_id": null,
                "documento_tipo_id": 1,
                "documento_tipo": {
                    "id": 1,
                    "nombre": "Pliegos de Bases y Condiciones Generales",
                    "descripcion": "Documento base del concurso",
                    "encriptado": false,
                    "obligatorio": true,
                    "de_concurso": true
                },
                "archivo": "pliegos_bases.pdf",
                "mimeType": "application/pdf",
                "extension": "pdf",
                "file_storage": "concursos/1/pliegos_bases.pdf",
                "encriptado": false
            }
        ],
        "documentos_requeridos": [
            {
                "id": 1,
                "nombre": "Pliegos de Bases y Condiciones Generales",
                "descripcion": "Documento base del concurso",
                "de_concurso": true,
                "encriptado": false,
                "obligatorio": true
            }
        ],
        "invitacion": {
            "id": 10,
            "concurso_id": 1,
            "proveedor_id": 123,
            "intencion": 0,
            "fecha_envio": "2024-01-10",
            "documentos": []
        }
    },
    "message": "Concurso encontrado correctamente."
}
```

### 2.3. Cambiar Intención de Participación

**PATCH** `/api/concursos/{concurso_id}/invitacion`

Permite al proveedor cambiar su intención de participación en el concurso.

**Parámetros:**
- `concurso_id` (integer): ID del concurso

**Body:**
```json
{
    "intencion": 1
}
```

**Valores de intención:**
- `0`: Pregunta (por defecto)
- `1`: Participa
- `2`: No participa
- `3`: Ofertó

**Respuesta:**
```json
{
    "success": true,
    "message": "Intención actualizada correctamente."
}
```

### 2.4. Obtener Tipos de Documentos

**GET** `/api/concursos/tipos-documentos`

Obtiene todos los tipos de documentos disponibles para concursos.

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Pliegos de Bases y Condiciones Generales",
            "descripcion": "Documento base del concurso",
            "de_concurso": true,
            "encriptado": false,
            "obligatorio": true,
            "tipo_documento_proveedor_id": null
        },
        {
            "id": 2,
            "nombre": "Pliego de Condiciones Particulares",
            "descripcion": "Condiciones específicas del concurso",
            "de_concurso": true,
            "encriptado": false,
            "obligatorio": true,
            "tipo_documento_proveedor_id": null
        }
    ],
    "message": "Tipos de documentos obtenidos correctamente."
}
```

### 2.5. Subir Documento

**POST** `/api/concursos/{concurso_id}/documentos`

Permite al proveedor subir un documento para el concurso.

**Parámetros:**
- `concurso_id` (integer): ID del concurso

**Body (multipart/form-data):**
- `documento_tipo_id` (integer): ID del tipo de documento
- `file` (file): Archivo a subir (máximo 10MB)

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "id": 15,
        "concurso_id": 1,
        "invitacion_id": 10,
        "documento_tipo_id": 1,
        "documento_tipo": {
            "id": 1,
            "nombre": "Pliegos de Bases y Condiciones Generales",
            "descripcion": "Documento base del concurso",
            "encriptado": false,
            "obligatorio": true,
            "de_concurso": true
        },
        "archivo": "mi_documento.pdf",
        "mimeType": "application/pdf",
        "extension": "pdf",
        "file_storage": "concursos/1/mi_documento.pdf",
        "encriptado": false
    },
    "message": "Documento subido correctamente. Pendiente de validación."
}
```

### 2.6. Obtener Documentos de la Invitación

**GET** `/api/concursos/{concurso_id}/documentos`

Obtiene todos los documentos subidos por el proveedor para este concurso.

**Parámetros:**
- `concurso_id` (integer): ID del concurso

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "id": 15,
            "concurso_id": 1,
            "invitacion_id": 10,
            "documento_tipo_id": 1,
            "documento_tipo": {
                "id": 1,
                "nombre": "Pliegos de Bases y Condiciones Generales",
                "descripcion": "Documento base del concurso",
                "encriptado": false,
                "obligatorio": true,
                "de_concurso": true
            },
            "archivo": "mi_documento.pdf",
            "mimeType": "application/pdf",
            "extension": "pdf",
            "file_storage": "concursos/1/mi_documento.pdf",
            "encriptado": false
        }
    ],
    "message": "Documentos de la invitación obtenidos correctamente."
}
```

### 2.7. Descargar Documento

**GET** `/api/concursos/{concurso_id}/documentos/{documento_id}/descargar`

Descarga un documento específico del concurso.

**Parámetros:**
- `concurso_id` (integer): ID del concurso
- `documento_id` (integer): ID del documento

**Respuesta:** Archivo descargado directamente

### 2.8. Verificar Documento de Proveedor

**GET** `/api/concursos/{concurso_id}/documentos/{documento_tipo_id}/verificar`

Verifica si el proveedor tiene un documento válido del tipo especificado en su perfil.

**Parámetros:**
- `concurso_id` (integer): ID del concurso
- `documento_tipo_id` (integer): ID del tipo de documento

**Respuesta (si existe):**
```json
{
    "success": true,
    "data": {
        "id": 25,
        "documento_tipo_id": 1,
        "archivo": "documento_proveedor.pdf",
        "mimeType": "application/pdf",
        "extension": "pdf",
        "vencimiento": "2024-12-31"
    },
    "message": "Documento válido encontrado."
}
```

**Respuesta (si no existe):**
```json
{
    "success": false,
    "message": "No hay documento válido para este tipo."
}
```

---

## 3. Códigos de Estado

- `200`: OK - Operación exitosa
- `201`: Created - Recurso creado exitosamente
- `400`: Bad Request - Datos de entrada inválidos
- `401`: Unauthorized - Token JWT inválido o faltante
- `404`: Not Found - Recurso no encontrado
- `422`: Unprocessable Entity - Error de validación
- `500`: Internal Server Error - Error interno del servidor

---

## 4. Manejo de Errores

Todas las respuestas de error siguen este formato:

```json
{
    "success": false,
    "message": "Descripción del error",
    "errors": {
        "campo": ["Mensaje de error específico"]
    }
}
```

---

## 5. Límites y Restricciones

- **Tamaño máximo de archivo:** 10MB por documento
- **Formatos soportados:** PDF, DOC, DOCX, XLS, XLSX, JPG, PNG
- **Token JWT:** Válido por 10 minutos
- **Rate limiting:** 100 requests por minuto por proveedor

---

## 6. Ejemplos de Uso

### 6.1. Flujo Completo de Participación

1. **Obtener concursos disponibles:**
   ```bash
   curl -H "Authorization: Bearer <token>" \
        https://api.ejemplo.com/api/concursos
   ```

2. **Ver detalles de un concurso:**
   ```bash
   curl -H "Authorization: Bearer <token>" \
        https://api.ejemplo.com/api/concursos/1
   ```

3. **Cambiar intención de participación:**
   ```bash
   curl -X PATCH \
        -H "Authorization: Bearer <token>" \
        -H "Content-Type: application/json" \
        -d '{"intencion": 1}' \
        https://api.ejemplo.com/api/concursos/1/invitacion
   ```

4. **Subir documento:**
   ```bash
   curl -X POST \
        -H "Authorization: Bearer <token>" \
        -F "documento_tipo_id=1" \
        -F "file=@mi_documento.pdf" \
        https://api.ejemplo.com/api/concursos/1/documentos
   ```

5. **Descargar documento del concurso:**
   ```bash
   curl -H "Authorization: Bearer <token>" \
        https://api.ejemplo.com/api/concursos/1/documentos/1/descargar \
        -o documento_descargado.pdf
   ```

---

## 7. Notas de Implementación

- Todos los endpoints verifican que el proveedor tenga acceso al concurso mediante la invitación
- Los documentos se almacenan en el disco configurado para concursos
- Se mantiene un registro de auditoría de todas las acciones
- Los archivos se validan por tipo MIME y extensión
- Se implementa rate limiting para prevenir abuso 