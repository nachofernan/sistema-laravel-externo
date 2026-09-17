<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="320" alt="Laravel Logo">
</p>

<h1 align="center">Portal de Proveedores</h1>

<p align="center">
  Cara pública, expuesta a internet, del sistema de gestión de proveedores y concursos de precios<br>
  de una empresa de energía. Cliente autenticado de una API interna vía JWT — no dueño de los datos.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white" alt="Laravel 11">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white" alt="PHP 8.2">
  <img src="https://img.shields.io/badge/Livewire-3-4E56A6" alt="Livewire 3">
  <img src="https://img.shields.io/badge/License-MIT-blue" alt="MIT License">
</p>

---

## De qué se trata

Este repositorio es una pieza dentro de un ecosistema Laravel modular más grande: un **sistema
interno** concentra toda la lógica de negocio, los datos sensibles y la verdad de proveedores y
concursos de precios de la empresa. Ese sistema nunca se expone directo a internet. Lo que sí se
expone es **este portal**: la puerta de entrada para que un proveedor externo, sin credenciales de
red interna, pueda loguearse, ver los concursos en los que participa, subir documentación y
gestionar su relación comercial con la empresa.

La decisión de arquitectura central es simple de enunciar y deliberada de sostener: **este portal no
guarda datos de negocio**. No hay una tabla `proveedores` ni `concursos` de verdad acá. Todo dato que
no sea estrictamente necesario para autenticar a un usuario se pide, en el momento, al sistema
interno vía una API HTTP protegida con JWT. El portal público es, en esencia, un cliente HTTP con una
capa fina de login/sesión propia encima — no una copia ni una réplica del sistema interno.

```
Navegador (proveedor externo, internet)
        │  HTTPS
        ▼
┌───────────────────────────────────────────┐
│  Portal de Proveedores (este repo)         │
│  Laravel 11 + Livewire 3 + Jetstream       │
│                                             │
│  · Login progresivo (CUIT + contraseña)    │
│  · JWT de sesión, nunca logueado           │
│  · Base local mínima: users, intentos      │
│    de login, tokens de reset               │
└─────────────────┬───────────────────────────┘
                  │  Bearer JWT sobre HTTPS
                  ▼
┌───────────────────────────────────────────┐
│  Sistema interno (otro Laravel, privado)   │
│  · Dueño real de proveedores y concursos   │
│  · Emite y valida los JWT                  │
│  · Nunca alcanzable directo desde afuera   │
└───────────────────────────────────────────┘
```

## Por qué está diseñado así

Cualquier request que llega a este proceso puede venir de un desconocido en internet, no de un
empleado autenticado en una red interna. Esa asimetría define cada decisión del proyecto:

- **El JWT es el activo más sensible que pasa por acá.** Autentica contra la API interna, se guarda
  únicamente en la sesión del servidor y nunca se loguea — ni completo ni parcial. Un middleware
  (`RefreshJwtToken`) lo renueva proactivamente antes de que expire, sin que el usuario lo note.
- **Sin base de datos de negocio propia.** La única base local que este portal es dueño de verdad es
  la que necesita Laravel/Jetstream para autenticar: usuarios del portal, intentos de login y tokens
  de recuperación de contraseña. Todo lo demás — proveedores, concursos, documentos — se resuelve en
  vivo contra la API del sistema interno a través de dos servicios de dominio
  (`ConcursosApiService`, `ProveedorApiService`), nunca con una conexión de base de datos directa.
- **Login progresivo a medida**, no el registro estándar de Fortify: el flujo confirma primero si un
  CUIT corresponde a un proveedor habilitado antes de ofrecer cuenta, con rate limiting por IP y
  bloqueo de cuenta tras intentos fallidos — la única defensa contra fuerza bruta, aplicada de forma
  consistente a todo endpoint que toque credenciales o CUITs.
- **Sin SPA.** Server-rendered con Blade y Livewire 3, con Alpine.js solo donde hace falta
  interactividad puntual. Menos superficie de ataque en el cliente, menos infraestructura de build.

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 11 · PHP 8.2 |
| Frontend | Livewire 3 · Blade · Alpine.js · Tailwind CSS (Vite) |
| Autenticación | Jetstream + Fortify (2FA disponible) · Sanctum para sesión |
| Integración | JWT (`firebase/php-jwt`) contra la API del sistema interno |
| Email | Microsoft Graph (`innoge/laravel-msgraph-mail`) para contraseñas temporales y reset |
| Testing | Pest / PHPUnit |

## Puesta en marcha local

Requiere PHP 8.2+, Composer, Node/npm y MySQL.

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

Además de las variables estándar de Laravel, hace falta configurar la integración con el sistema
interno (no incluidas en `.env.example` de fábrica): `PLATAFORMA_API_URL` y `JWT_SECRET` para hablar
con la API, y las credenciales de `DB_DATABASE_LOCAL` para la base propia del portal.

```bash
php artisan migrate
npm run dev      # watch de assets
php artisan serve
```

### Tests

```bash
php artisan test                      # suite completa
php artisan test --filter=NombreTest  # acotado
```

## Documentación

El detalle de arquitectura (qué vive local vs. qué viene de la API, contrato de los endpoints
consumidos, decisiones de diseño) vive en `docs/`, separado de este README para no mezclar la
presentación del proyecto con su bitácora técnica interna.

## Licencia

Proyecto construido sobre el framework [Laravel](https://laravel.com), open-source bajo
[licencia MIT](https://opensource.org/licenses/MIT).
