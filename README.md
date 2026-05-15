# New Harvest — Documentación del Proyecto

> Backend web interno y API PHP para la gestión de vouchers y remitos de combustible de **New Harvest**.

---

## Tabla de contenidos

1. [¿Qué es el proyecto?](#1-qué-es-el-proyecto)
2. [Relación con `mobnewharvest`](#2-relación-con-mobnewharvest)
3. [Tecnologías principales](#3-tecnologías-principales)
4. [Arquitectura y estructura de carpetas](#4-arquitectura-y-estructura-de-carpetas)
5. [Flujo funcional por rol](#5-flujo-funcional-por-rol)
6. [Módulo RRHH y empresas](#6-módulo-rrhh-y-empresas)
7. [API consumida por la app móvil](#7-api-consumida-por-la-app-móvil)
8. [Modelo de datos (tablas principales)](#8-modelo-de-datos-tablas-principales)
9. [Generación de PDF y firmas](#9-generación-de-pdf-y-firmas)
10. [Cómo correr el proyecto](#10-cómo-correr-el-proyecto)
11. [Assets y recursos estáticos](#11-assets-y-recursos-estáticos)
12. [Observaciones importantes](#12-observaciones-importantes)

---

## 1. ¿Qué es el proyecto?

**newHarvest** es el sistema web/backoffice en PHP que centraliza la operación de remitos de transporte y combustible.

Permite:

- Autenticación por usuario con rol.
- Carga de **vouchers** (traslados) con firma digital del pasajero.
- Carga de **remitos de combustible**.
- Aprobación y administración desde RRHH.
- Gestión de empresas para clasificación de vouchers aprobados.
- Generación de PDF del voucher con logos y monto.

---

## 2. Relación con `mobnewharvest`

Este repositorio es el **backend web/API** al que se conecta directamente la app móvil:

- Repositorio móvil: `joaquinhega/mobnewharvest`
- Dominio productivo mencionado en la app: `https://newharvest.com.ar/vouchers/api/`

La app móvil consume endpoints de este backend para:

- Login de chofer.
- Alta y consulta de vouchers.
- Alta y consulta de remitos de combustible.
- Obtención del último ID de remito por letra de chofer.

---

## 3. Tecnologías principales

| Tecnología | Rol en el proyecto |
|---|---|
| **PHP** | Lógica web, vistas, controladores y API |
| **MySQL / MariaDB** | Persistencia de usuarios, vouchers, combustibles y empresas |
| **JavaScript (vanilla + AJAX)** | Filtros, paginado dinámico y modales en vistas RRHH |
| **FPDF 1.86** | Generación de PDF para vouchers |
| **Signature Pad (CDN)** | Captura de firma manuscrita en navegador |
| **CSS** | Estilos UI del panel web |

---

## 4. Arquitectura y estructura de carpetas

```
newHarvest/
├── index.php                  # Login web
├── Controller/
│   ├── loguear.php            # Proceso de login web
│   ├── cerrarSesion.php       # Logout
│   ├── generarFirma.php       # Utilidad de guardado de firma
│   └── generarPdf.php         # PDF de voucher con FPDF
├── Model/
│   ├── conexion.php           # Conexión MySQL
│   ├── guardar_voucher.php    # Alta voucher (web)
│   ├── guardar_combustible.php# Alta combustible (web)
│   ├── aprobar_*.php          # Aprobaciones RRHH
│   ├── editarVoucher.php      # Edición voucher
│   ├── eliminarVoucher.php    # Borrado lógico voucher
│   ├── asignarEmpresa.php     # Mover/desaprobar voucher
│   ├── setMontoVoucher.php    # Asignación de monto para PDF
│   ├── RemitoV.php            # Cálculo último remito voucher
│   └── RemitoC.php            # Cálculo último remito combustible
├── View/
│   ├── chofer*.php            # Vistas de chofer
│   ├── rrhh*.php              # Vistas de RRHH
│   ├── listaEmpresa.php       # CRUD de empresas
│   ├── empresa.php            # Vouchers por empresa + PDF
│   ├── filtrarVouchers.php    # Endpoint AJAX para filtros/paginado
│   └── filtrarCombustible.php # Endpoint AJAX para filtros/paginado
├── api/
│   ├── login.php
│   ├── getVouchers.php
│   ├── getRemitos.php
│   ├── guardarVoucher.php
│   ├── guardarCombustible.php
│   ├── editVoucher.php
│   ├── editRemito.php
│   ├── deleteVoucher.php
│   ├── deleteRemito.php
│   └── remitoV.php
├── Estilo/styles.css          # Estilos globales
├── assets/                    # Logos e íconos
├── firmas/                    # Firmas PNG guardadas
└── fpdf186/                   # Librería FPDF embebida
```

---

## 5. Flujo funcional por rol

### Chofer

1. Inicia sesión en `index.php`.
2. Accede a:
   - `choferVoucher.php` para cargar voucher.
   - `choferCombustible.php` para cargar remito combustible.
   - `choferVerVouchers.php` para ver/editar/eliminar sus vouchers.
3. En vouchers, completa datos + firma (`firma.php`) y guarda en DB.

### RRHH

1. Accede a `rrhhVoucher.php` y `rrhhCombustible.php`.
2. Filtra por texto y rango de fechas.
3. Aprueba registros pendientes.
4. En vouchers aprobados puede mover a empresa o desaprobar.

---

## 6. Módulo RRHH y empresas

En `listaEmpresa.php` se puede:

- Crear empresa.
- Subir/editar/eliminar logo (PNG/JPG).
- Editar nombre.
- Borrado lógico de empresa.

En `empresa.php` se visualizan vouchers asociados a esa empresa, se asigna/modifica monto y se genera el PDF final del voucher.

---

## 7. API consumida por la app móvil

Base esperada en producción:

```
https://newharvest.com.ar/vouchers/api/
```

| Endpoint | Método | Descripción |
|---|---|---|
| `/api/login.php` | POST (JSON) | Autenticación de usuario |
| `/api/getVouchers.php` | GET | Lista vouchers por letra (`HTTP_LETRA`) |
| `/api/getRemitos.php` | GET | Lista combustibles por nombre (`HTTP_NOMBRE`) |
| `/api/guardarVoucher.php` | POST (form-data) | Alta voucher con firma (`signature`) |
| `/api/guardarCombustible.php` | POST (JSON) | Alta remito combustible |
| `/api/editVoucher.php` | POST (JSON) | Edición de voucher |
| `/api/editRemito.php` | POST (JSON) | Edición de remito combustible |
| `/api/deleteVoucher.php` | POST (JSON) | Borrado lógico voucher |
| `/api/deleteRemito.php` | POST (JSON) | Borrado lógico remito |
| `/api/remitoV.php` | POST (JSON) | Último ID de remito por letra |

---

## 8. Modelo de datos (tablas principales)

Tablas referenciadas en el código:

- `usuario`: autenticación y rol (`Usuario`, `Contrasena`, `Rol`, `Letra`, `Nombre`).
- `voucher`: datos de traslado, firma, estado de aprobación y borrado lógico.
- `combustible`: remitos de combustible, estado de aprobación y borrado lógico.
- `empresa`: catálogo de empresas, logo y estado de borrado.

Campos/flags de estado usados frecuentemente:

- `aprobado` (`0/1`)
- `borrado` (`0/1`)
- `id_empresa` (asignación de voucher a empresa)
- `Monto` (valor para salida PDF)

---

## 9. Generación de PDF y firmas

### Firma digital

- Captura en `View/firma.php` con `SignaturePad`.
- Se envía en base64 y se almacena como PNG en `firmas/`.
- La ruta se guarda en la tabla `voucher`.

### PDF de voucher

- `Controller/generarPdf.php` usa `fpdf186/fpdf.php`.
- Incluye:
  - Logo New Harvest.
  - Logo de empresa (si existe).
  - Datos del viaje.
  - Monto.
  - Firma del pasajero.

---

## 10. Cómo correr el proyecto

### Requisitos

- PHP 8.x (compatible con `mysqli`).
- MySQL/MariaDB con base `sistema_vouchers` (o equivalente configurado en `Model/conexion.php`).
- Servidor Apache/Nginx o servidor embebido de PHP.

### Configuración de base de datos

Editar:

- `Model/conexion.php`

Parámetros:

- `$server`
- `$user`
- `$pass`
- `$db`

### Ejecución local rápida (desarrollo)

```bash
cd /ruta/a/newHarvest
php -S localhost:8000
```

Abrir en navegador:

```
http://localhost:8000
```

### Validación de sintaxis PHP

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

---

## 11. Assets y recursos estáticos

```
assets/
├── logo-newHarvest.png
├── logo-newHarvest-negro.png
├── boton-editar.png
├── boton-eliminar.png
└── logos/                  # logos de empresas subidos desde el panel
```

Carpeta de firmas:

```
firmas/
```

---

## 12. Observaciones importantes

- El backend web y API comparten la misma base de datos.
- Se utiliza **borrado lógico** para vouchers, combustibles y empresas.
- Hay endpoints API consumidos por la app móvil y vistas web para operación interna.
- FPDF está versionado dentro del repositorio (`fpdf186`).

---

*Desarrollado para uso interno de New Harvest · newharvest.com.ar*
