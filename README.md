# Laravel Livewire Starter Kit

Panel de administracion construido con Laravel 13, Livewire 4 y Flux UI. Incluye gestion de usuarios, roles y permisos, catalogos parametrizables, autenticacion con 2FA, y soporte bilingue (ingles/espanol).

## Stack tecnologico

| Capa | Tecnologia |
|---|---|
| Backend | PHP 8.4, Laravel 13, Fortify 1.x |
| Frontend | Livewire 4, Flux UI Pro 2.x, Tailwind CSS 4, Alpine.js |
| Autorizacion | Spatie Laravel Permission 7.x |
| Media | Spatie Media Library 11.x |
| Testing | Pest 4, Larastan 3 |
| Build | Vite 7, Laravel Pint |
| Dev environment | Laravel Sail (Docker) |

## Requisitos

- PHP >= 8.4
- Composer
- Node.js & npm
- SQLite (por defecto) o MySQL/PostgreSQL
- Licencia de [Flux UI Pro](https://fluxui.dev)

## Instalacion

```bash
# Clonar el repositorio
git clone <repo-url> && cd laravel-starter

# Instalar dependencias, generar key, migrar y compilar assets
composer setup

# Sembrar datos iniciales
php artisan db:seed
```

El comando `composer setup` ejecuta: `composer install`, genera `.env` y `APP_KEY`, corre migraciones y ejecuta `npm install && npm run build`.

### Credenciales por defecto

| Campo | Valor |
|---|---|
| Email | `admin@localhost` |
| Password | `password` |

## Desarrollo

```bash
# Iniciar todos los servicios (server, queue, logs, vite)
composer dev
```

Esto lanza en paralelo: servidor PHP, worker de colas, visor de logs (Pail) y Vite dev server.

## Arquitectura

El proyecto sigue **DDD pragmatico + SOLID**, organizado por contexto funcional:

```
app/
├── Domain/                  # Reglas de negocio puras, value objects, enums
│   ├── Auth/                # Registro de permisos (PermissionRegistry)
│   ├── Table/               # Abstracciones de columnas, filtros, card layout
│   └── Users/               # Configuracion de roles, normalizacion
├── Actions/                 # Casos de uso (un handle() por clase)
│   ├── Countries/
│   ├── IdentificationDocumentTypes/
│   ├── Roles/
│   ├── Users/
│   └── Fortify/             # Acciones de autenticacion
├── Infrastructure/          # Servicios de UI (ModalService, ToastService)
├── Models/                  # Eloquent: User, Role, Country, IdentificationDocumentType
├── Policies/                # Autorizacion por modelo
├── Livewire/                # Componentes de UI (settings, logout)
└── Providers/               # Registro de servicios y Fortify
```

Las vistas usan **Multi-file Components (MFC)** de Livewire 4, colocadas en:

```
resources/views/
├── pages/                   # Paginas completas (MFC)
│   ├── users/⚡index/
│   ├── roles/⚡index/
│   ├── countries/⚡index/
│   └── identification-document-types/⚡index/
├── components/              # Componentes Blade reutilizables
│   ├── table/               # Sistema de tablas (headers, cells, cards)
│   └── roles/, users/, ...  # Formularios modales
└── livewire/auth/           # Vistas de autenticacion (Fortify)
```

## Modulos

### Usuarios

CRUD completo con avatar (Media Library), informacion personal (telefono, documento, direccion), asignacion de roles, activacion/desactivacion y gestion de contraseñas.

### Roles y Permisos

Gestion de roles con etiquetas localizadas (en/es), color, orden, estado activo/inactivo y rol por defecto. Los permisos se descubren automaticamente escaneando las Policies del proyecto.

### Catalogos

- **Paises** — nombres localizados, codigos ISO alpha-2/3, codigo telefonico
- **Tipos de documento de identificacion** — codigo, nombres localizados

Todos los catalogos soportan activacion/desactivacion y ordenamiento.

### Autenticacion

Basada en Laravel Fortify: login, registro, verificacion de email, recuperacion de contraseña, autenticacion de dos factores (TOTP) con codigos de recuperacion.

### Configuracion de usuario

- **Perfil** — nombre y email
- **Apariencia** — tema claro/oscuro
- **Seguridad** — cambio de contraseña, 2FA, eliminacion de cuenta

## Sistema de tablas

Infraestructura reutilizable de tablas con:

- Columnas tipadas: `TextColumn`, `BadgeColumn`, `ToggleColumn`, `DateColumn`, `AvatarColumn`, `ActionsColumn`
- Busqueda, ordenamiento y filtros
- Layout responsivo con **CardZone** (Header/Body/Footer/Hidden) para vista movil
- Paginacion integrada

## Localizacion

Soporte completo en ingles y espanol. Los textos se organizan en:

- `lang/{locale}.json` — strings compartidos de UI
- `lang/{locale}/{dominio}.php` — strings por modulo (users, roles, countries, etc.)

Los modelos con atributos localizados (`en_name`, `es_name`) exponen `localizedName()`.

## Rutas principales

```
GET  /                                → Pagina de bienvenida
GET  /dashboard                       → Dashboard (auth + verified)

# Admin (auth + verified + role:admin)
GET  /users                           → Listado de usuarios
GET  /users/{user}                    → Detalle de usuario
GET  /countries                       → Listado de paises
GET  /roles                           → Listado de roles
GET  /identification-document-types   → Tipos de documento

# Settings (auth)
GET  /settings/profile                → Perfil
GET  /settings/appearance             → Apariencia
GET  /settings/security               → Seguridad
```

## Testing

```bash
# Ejecutar todos los tests
php artisan test --compact

# Filtrar por nombre
php artisan test --compact --filter=UserTest

# Suite completa (lint + analisis estatico + tests en paralelo)
composer test
```

Los tests usan Pest 4 con `RefreshDatabase`. Helpers disponibles: `makeAdmin()`, `makeGuest()`.

## Scripts de Composer

| Comando | Descripcion |
|---|---|
| `composer setup` | Instalacion completa del proyecto |
| `composer dev` | Levantar todos los servicios de desarrollo |
| `composer lint` | Formatear codigo con Pint |
| `composer lint:check` | Verificar formato sin modificar |
| `composer analyse` | Analisis estatico con Larastan |
| `composer test` | Lint + analisis + tests en paralelo |

## Licencia

MIT
