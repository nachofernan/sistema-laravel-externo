# API de Proveedores y Concursos

Este documento describe la API RESTful para la gestión de proveedores y concursos. Está diseñada para ser consumida por el frontend del portal de proveedores y concursos, y sigue buenas prácticas de seguridad, estructura y claridad.

---

## 1. Autenticación

- **Tipo:** JWT (JSON Web Token)
- **Header requerido:**
  - `Authorization: Bearer <token>`
- **Obtención del token:**
  - Endpoint: `POST /api/generate-token`
  - Parámetros: según lógica de login (ver backend)

---

## 2. Endpoints públicos (sin autenticación)

### 2.1. Generar token
- **POST** `/api/generate-token`
- **Body:**
  - `{ "cuit": "string", "password": "string" }`
- **Respuesta:**
  - `{ "success": true, "token": "..." }`

### 2.2. Validar proveedor
- **POST** `/api/validate-provider`
- **Body:**
  - `{ "cuit": "string" }`
- **Respuesta:**
  - `{ "success": true, "data": { ... } }`

---

## 3. Endpoints protegidos (requieren JWT)

Todos los siguientes endpoints requieren el header `Authorization: Bearer <token>`.

### 3.1. Proveedores

#### 3.1.1. Obtener datos completos de un proveedor
- **GET** `/api/proveedores/{cuit}`
- **Respuesta:**
  - `{ "success": true, "data": { ...datos completos del proveedor... }, "message": "Proveedor encontrado." }`

#### 3.1.2. Obtener tipos de documentos y apoderados
- **GET** `/api/proveedores/tipos-documentos`
- **Respuesta:**
  - `{ "success": true, "data": { "tipos_documentos": [...], "tipos_apoderados": [...] }, "message": "Tipos obtenidos." }`

#### 3.1.3. Obtener tipos de rubros y subrubros
- **GET** `/api/proveedores/tipos-rubros`
- **Respuesta:**
  - `{ "success": true, "data": [ ...rubros y subrubros... ], "message": "Rubros y subrubros obtenidos." }`

#### 3.1.4. Asociar subrubros a un proveedor
- **POST** `/api/proveedores/{cuit}/subrubros`
- **Body:**
  - `{ "subrubro_ids": [1,2,3] }`
- **Respuesta:**
  - `{ "success": true, "message": "Subrubros actualizados correctamente." }`

#### 3.1.5. Subir documento de proveedor
- **POST** `/api/proveedores/{cuit}/documentos`
- **Body (form-data):**
  - `documento_tipo_id`: ID del tipo de documento
  - `file`: archivo a subir
  - `vencimiento` (opcional): fecha de vencimiento (YYYY-MM-DD)
- **Respuesta:**
  - `{ "success": true, "data": { ...documento... }, "message": "Documento subido correctamente. Pendiente de validación." }`

#### 3.1.6. Descargar documento de proveedor
- **GET** `/api/proveedores/{cuit}/documentos/{documento_id}/descargar`
- **Respuesta:**
  - Descarga directa del archivo (si autorizado y válido)

---

### 3.2. Concursos

#### 3.2.1. Listar concursos donde el proveedor es invitado
- **GET** `/api/concursos`
- **Respuesta:**
  - `{ "success": true, "data": [ ...concursos... ], "message": "Concursos obtenidos." }`

#### 3.2.2. Obtener info completa de un concurso
- **GET** `/api/concursos/{concurso_id}`
- **Respuesta:**
  - `{ "success": true, "data": { ...concurso... }, "message": "Concurso encontrado." }`

#### 3.2.3. Cambiar intención en la invitación
- **PATCH** `/api/concursos/{concurso_id}/invitacion`
- **Body:**
  - `{ "intencion": 0|1|2|3 }` (0: pregunta, 1: participa, 2: no participa, 3: ofertó)
- **Respuesta:**
  - `{ "success": true, "message": "Intención actualizada correctamente." }`

#### 3.2.4. Subir documento asociado a la invitación
- **POST** `/api/concursos/{concurso_id}/documentos`
- **Body (form-data):**
  - `documento_tipo_id`: ID del tipo de documento
  - `file`: archivo a subir
- **Respuesta:**
  - `{ "success": true, "data": { ...documento... }, "message": "Documento subido correctamente. Pendiente de validación." }`

#### 3.2.5. Verificar si un documento requerido ya está subido y validado como proveedor
- **GET** `/api/concursos/{concurso_id}/documentos/{documento_tipo_id}/verificar`
- **Respuesta:**
  - Si existe: `{ "success": true, "data": { ...documento... }, "message": "Documento válido encontrado." }`
  - Si no existe: `{ "success": false, "message": "No hay documento válido para este tipo." }`

---

## 4. Notas de uso y recomendaciones

- **Todas las respuestas** siguen el formato `{ success, data, message }` para facilitar el manejo en el frontend.
- **Errores**: Si ocurre un error, el campo `success` será `false` y se incluirá un mensaje descriptivo.
- **Autenticación**: El token JWT debe enviarse en cada request protegido.
- **Subida de archivos**: Usar `multipart/form-data` y el campo `file`.
- **Fechas**: Formato `YYYY-MM-DD`.
- **IDs**: Siempre usar los IDs reales de la base de datos.

---

## 5. Ejemplo de flujo de uso

1. El usuario se loguea y obtiene un token JWT.
2. El frontend guarda el token y lo envía en cada request protegido.
3. El usuario puede consultar sus datos, rubros, documentos, concursos, etc.
4. Puede asociar subrubros, subir documentos, descargar archivos, y gestionar su participación en concursos.

---

## 6. Contacto y soporte

Para dudas sobre la API, contactar al equipo backend. 