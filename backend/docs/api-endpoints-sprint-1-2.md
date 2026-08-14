# New Harvest API - Sprint 1.2 Endpoint Catalog

Documento de referencia de los endpoints disponibles hasta este punto: login, salud de API, vouchers y companies.

## Convenciones generales

- Base URL local: `http://127.0.0.1:8000/api`
- Versión actual: `v1`
- Autenticación: `Bearer token` emitido por Sanctum
- Formato de respuesta estándar: `success`, `message`, `data`, `status_code`, `timestamp`
- Listados paginados: incluyen `meta.pagination`
- Tokens: cada login genera un token nuevo y no invalida los anteriores

Ejemplo de header autenticado:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## 1) Login

**Nombre:** Login de usuario

**Endpoint:** `POST /api/v1/auth/login`

**Auth:** No requiere

**Body JSON:**

```json
{
  "login": "chofer01",
  "password": "secret123"
}
```

**Respuesta exitosa JSON:**

```json
{
  "success": true,
  "message": "Login exitoso",
  "data": {
    "token_type": "Bearer",
    "access_token": "<token>",
    "user": {
      "id_usuario": 3,
      "username": "chofer01",
      "first_name": "Juan",
      "last_name": "Pérez",
      "email": "juan@example.com",
      "letter": "A",
      "active": true,
      "role": {
        "id": 3,
        "name": "chofer",
        "description": "Chofer Operativo"
      }
    }
  },
  "status_code": 200,
  "timestamp": "2026-08-12T12:00:00Z"
}
```

## 2) Logout

**Nombre:** Cerrar sesión

**Endpoint:** `POST /api/v1/auth/logout`

**Auth:** Sí, Bearer token

**Body JSON:** No requiere

**Comportamiento:** invalida solo el token con el que se llama al endpoint.

**Respuesta exitosa JSON:**

```json
{
  "success": true,
  "message": "Logout exitoso",
  "data": null,
  "status_code": 200,
  "timestamp": "2026-08-12T12:00:00Z"
}
```

## 3) Health check

**Nombre:** Verificación de API

**Endpoint:** `GET /api/v1/health-check`

**Auth:** No requiere

**Respuesta exitosa JSON:**

```json
{
  "status": "success",
  "message": "New Harvest API v1 funcionando correctamente",
  "timestamp": "2026-08-12T12:00:00Z"
}
```

## 4) Usuario autenticado

**Nombre:** Usuario actual

**Endpoint:** `GET /api/user`

**Auth:** Sí, Bearer token

**Respuesta:** Devuelve el usuario autenticado completo desde Sanctum.

## 5) Companies

### 5.1 Listar companies

**Nombre:** Listado de empresas

**Endpoint:** `GET /api/v1/companies`

**Auth:** Sí, Bearer token

**Query params opcionales:**

- `search`: filtra por nombre
- `per_page`: cantidad por página

**Respuesta exitosa JSON:**

```json
{
  "success": true,
  "message": "Empresas listadas correctamente",
  "data": [
    {
      "id": 1,
      "name": "Empresa Ejemplo",
      "path": "storage/logos/empresa-ejemplo.png",
      "borrado": false,
      "vouchers_count": 12,
      "created_at": "2026-08-12T12:00:00Z",
      "updated_at": "2026-08-12T12:00:00Z"
    }
  ],
  "status_code": 200,
  "timestamp": "2026-08-12T12:00:00Z",
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 1,
      "last_page": 1,
      "has_more_pages": false
    }
  }
}
```

### 5.2 Crear company

**Nombre:** Alta de empresa

**Endpoint:** `POST /api/v1/companies`

**Auth:** Sí, Bearer token

**Permisos:** `admin` y `rrhh`

**Body JSON:**

```json
{
  "name": "Empresa Ejemplo",
  "path": "storage/logos/empresa-ejemplo.png"
}
```

**Campos:**

- `name` obligatorio
- `path` opcional

**Respuesta exitosa JSON:**

```json
{
  "success": true,
  "message": "Empresa creada correctamente",
  "data": {
    "id": 1,
    "name": "Empresa Ejemplo",
    "path": "storage/logos/empresa-ejemplo.png",
    "borrado": false,
    "vouchers_count": 0,
    "created_at": "2026-08-12T12:00:00Z",
    "updated_at": "2026-08-12T12:00:00Z"
  },
  "status_code": 201,
  "timestamp": "2026-08-12T12:00:00Z"
}
```

### 5.3 Ver company

**Nombre:** Detalle de empresa

**Endpoint:** `GET /api/v1/companies/{company}`

**Auth:** Sí, Bearer token

**Respuesta:** Igual al resource de la empresa.

### 5.4 Editar company

**Nombre:** Actualización de empresa

**Endpoint:** `PUT /api/v1/companies/{company}` o `PATCH /api/v1/companies/{company}`

**Auth:** Sí, Bearer token

**Permisos:** `admin` y `rrhh`

**Body JSON:**

```json
{
  "name": "Empresa Actualizada",
  "path": "storage/logos/empresa-actualizada.png",
  "borrado": false
}
```

**Campos:**

- `name` opcional pero validado si se envía
- `path` opcional
- `borrado` opcional

### 5.5 Eliminar company

**Nombre:** Baja lógica de empresa

**Endpoint:** `DELETE /api/v1/companies/{company}`

**Auth:** Sí, Bearer token

**Permisos:** `admin` y `rrhh`

**Efecto:** marca `borrado = true`

## 6) Vouchers

### 6.1 Listar vouchers

**Nombre:** Listado de vouchers

**Endpoint:** `GET /api/v1/vouchers`

**Auth:** Sí, Bearer token

**Query params opcionales:**

- `company_id`: filtra por empresa
- `status`: `pendiente` o `aprobado`
- `date_from`: fecha desde `YYYY-MM-DD`
- `date_to`: fecha hasta `YYYY-MM-DD`
- `per_page`: cantidad por página

**Regla de acceso:**

- `admin` y `rrhh` ven todos
- `chofer` ve solo sus propios vouchers

**Respuesta exitosa JSON:**

```json
{
  "success": true,
  "message": "Vouchers listados correctamente",
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "user_id": 3,
      "origin": "Aeropuerto Mendoza",
      "destination": "Hotel NH Mendoza",
      "date": "2026-08-12",
      "amount": "15000.00",
      "observation": "Traslado corporativo",
      "status": "pendiente",
      "borrado": false,
      "created_at": "2026-08-12T12:00:00Z",
      "updated_at": "2026-08-12T12:00:00Z",
      "company": {
        "id": 1,
        "name": "Empresa Ejemplo",
        "path": "storage/logos/empresa-ejemplo.png",
        "borrado": false
      },
      "user": {
        "id_usuario": 3,
        "username": "chofer01",
        "first_name": "Juan",
        "last_name": "Pérez",
        "letter": "A"
      }
    }
  ],
  "status_code": 200,
  "timestamp": "2026-08-12T12:00:00Z",
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 1,
      "last_page": 1,
      "has_more_pages": false
    }
  }
}
```

### 6.2 Crear voucher

**Nombre:** Alta de voucher

**Endpoint:** `POST /api/v1/vouchers`

**Auth:** Sí, Bearer token

**Body JSON:**

```json
{
  "company_id": 1,
  "origin": "Aeropuerto Mendoza",
  "destination": "Hotel NH Mendoza",
  "date": "2026-08-12",
  "amount": 15000.00,
  "observation": "Traslado corporativo"
}
```

**Campos obligatorios:**

- `origin`
- `destination`
- `date`

**Campos opcionales:**

- `company_id`
- `amount`
- `observation`

**Regla de negocio:**

- `user_id` se toma del token autenticado
- `status` se asigna como `pendiente`
- `borrado` se asigna como `false`

**Respuesta exitosa JSON:**

```json
{
  "success": true,
  "message": "Voucher creado correctamente",
  "data": {
    "id": 1,
    "company_id": 1,
    "user_id": 3,
    "origin": "Aeropuerto Mendoza",
    "destination": "Hotel NH Mendoza",
    "date": "2026-08-12",
    "amount": "15000.00",
    "observation": "Traslado corporativo",
    "status": "pendiente",
    "borrado": false
  },
  "status_code": 201,
  "timestamp": "2026-08-12T12:00:00Z"
}
```

### 6.3 Ver voucher

**Nombre:** Detalle de voucher

**Endpoint:** `GET /api/v1/vouchers/{voucher}`

**Auth:** Sí, Bearer token

**Regla de acceso:**

- `admin` y `rrhh` pueden ver todo
- `chofer` solo su voucher

### 6.4 Editar voucher

**Nombre:** Actualización de voucher

**Endpoint:** `PUT /api/v1/vouchers/{voucher}` o `PATCH /api/v1/vouchers/{voucher}`

**Auth:** Sí, Bearer token

**Body JSON:**

```json
{
  "company_id": 1,
  "origin": "Aeropuerto El Plumerillo",
  "destination": "Hotel NH Mendoza",
  "date": "2026-08-12",
  "amount": 15500.00,
  "observation": "Cambio de origen"
}
```

**Campos editables:**

- `company_id`
- `origin`
- `destination`
- `date`
- `amount`
- `observation`

### 6.5 Eliminar voucher

**Nombre:** Baja lógica de voucher

**Endpoint:** `DELETE /api/v1/vouchers/{voucher}`

**Auth:** Sí, Bearer token

**Efecto:** marca `borrado = true`

### 6.6 Aprobar voucher

**Nombre:** Aprobación de voucher

**Endpoint:** `PATCH /api/v1/vouchers/{voucher}/approve`

**Auth:** Sí, Bearer token

**Permisos:** `admin` y `rrhh`

**Body JSON opcional:**

```json
{
  "company_id": 1
}
```

**Efecto:**

- cambia `status` a `aprobado`
- si se envía `company_id`, lo asigna también

## 7) Resumen rápido de estados

- `voucher.status`: `pendiente` | `aprobado`
- `borrado`: `false` = visible, `true` = baja lógica

## 8) Próximo bloque pendiente

- Modelado de combustible
- API REST de combustible
- Eventual carga/migración de datos legacy si hace falta

## 9) Notas de troubleshooting

- En clientes como Postman o Thunder Client, enviar siempre `Accept: application/json` junto con `Authorization: Bearer <token>` para forzar respuestas JSON.
- Si el alta o edición de voucher devuelve `company_id` inválido, primero crear una empresa con `POST /api/v1/companies` y usar su `id` real.
- Si una petición autenticada devuelve `401`, revisar que el token se esté enviando exactamente en `Authorization: Bearer <token>` y que no haya espacios extra.