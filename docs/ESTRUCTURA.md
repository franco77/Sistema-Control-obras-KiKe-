# Estructura de carpetas

Dónde va cada cosa y por qué.

```
crm-reformas/
│
├── app/
│   ├── Console/Commands/              Tareas programadas (caducidades, retrasos, recordatorios)
│   │
│   ├── Enums/                         Vocabulario del dominio (22 enums con label() y color())
│   │   └── Concerns/HasLabel.php      options(), values(), is()/isNot() para todos
│   │
│   ├── Exceptions/                    Excepciones de negocio, convertidas en mensaje legible
│   │   ├── InvalidTransitionException.php
│   │   └── ProviderNotAssignableException.php
│   │
│   ├── Http/
│   │   ├── Controllers/               Solo lo que no es Livewire: ficheros, PDF y entrada al portal
│   │   │   ├── DocumentController.php     descarga con control de acceso
│   │   │   ├── PhotoController.php        servido de fotos + miniaturas
│   │   │   ├── QuotePdfController.php     PDF del presupuesto (panel y portal)
│   │   │   └── Portal/PortalEntryController.php   canje token → sesión
│   │   └── Middleware/
│   │       ├── StartPortalSession.php     revalida el token en cada petición
│   │       ├── EnsurePortalAbility.php    exige el permiso concreto de la ruta
│   │       └── TrackLastLogin.php
│   │
│   ├── Livewire/
│   │   ├── Concerns/                  WithToasts · WithSorting · InteractsWithPortal
│   │   ├── Forms/                     Objetos de formulario (ClientData…)
│   │   ├── Admin/
│   │   │   ├── Dashboard/             Cuadro de mando
│   │   │   ├── Clients/               Índice, formulario, ficha, inmuebles, contactos
│   │   │   ├── Providers/             Índice, formulario, ficha (docs, disponibilidad, valoraciones)
│   │   │   ├── Quotes/                Índice, constructor de partidas, ficha con versiones
│   │   │   ├── Projects/              Índice, tablero, ficha + fases, incidencias, fotos, extras, diario
│   │   │   ├── Calendar/              Agenda mensual y lista
│   │   │   ├── Catalog/               Banco de precios
│   │   │   ├── Documents/             Vista transversal de caducidades
│   │   │   ├── Conversations/         Bandeja y hilo de mensajes
│   │   │   ├── Settings/              Configuración de empresa y oficios
│   │   │   └── Shared/                Reutilizables: DocumentManager, NotificationBell
│   │   └── Portal/                    Cara del cliente y del proveedor (sin login)
│   │       ├── QuoteReview.php            ver, descargar y decidir el presupuesto
│   │       ├── ProjectOverview.php        avance, fases, novedades
│   │       ├── ProjectTasks.php           trabajos hechos y pendientes
│   │       ├── ProjectGallery.php         fotos con visor
│   │       ├── ProjectIncidents.php
│   │       ├── ProjectExtras.php          aprobación de extras
│   │       ├── ProjectDocuments.php
│   │       ├── ProjectMessages.php        dudas y consultas
│   │       ├── ExtraDecision.php          extra suelto por su propio enlace
│   │       └── Provider/ProviderTasks.php portal del autónomo
│   │
│   ├── Models/
│   │   └── Concerns/                  HasSequentialCode · RecordsActivity · HasDocuments · HasPortalTokens
│   │
│   ├── Notifications/
│   │   ├── Client/                    Presupuesto, extra, avances, incidencia, entrega
│   │   ├── Provider/                  Tarea asignada, documentación por caducar
│   │   └── Internal/                  Decisiones del cliente, retrasos, mensajes, recordatorios
│   │
│   ├── Policies/                      Autorización por modelo (auto-descubiertas)
│   │
│   ├── Services/                      LA LÓGICA DE NEGOCIO VIVE AQUÍ
│   │   ├── Branding/                  LogoService (subida, reescalado y consumo del logo)
│   │   ├── Quotes/                    Calculator · Workflow · VersionService · ConversionService · NumberGenerator
│   │   ├── Projects/                  ProgressService · CostService · TaskAssignmentService · ExtraWorkflow
│   │   ├── Portal/                    PortalTokenService
│   │   ├── Documents/                 DocumentService · PhotoService
│   │   └── Notifications/             NotificationDispatcher
│   │
│   └── Support/helpers.php            setting() · money() · percent()
│
├── bootstrap/app.php                  Rutas, alias de middleware, excepciones y planificador
│
├── config/                            Estándar de Laravel + permission.php
│
├── database/
│   ├── migrations/                    37 migraciones, agrupadas por bloque funcional
│   └── seeders/
│       ├── RoleSeeder.php             Permisos por módulo y 4 roles
│       ├── SettingSeeder.php          Configuración inicial de la empresa
│       ├── TradeSeeder.php            14 oficios
│       ├── CatalogSeeder.php          33 partidas tipo con coste y PVP
│       └── DemoDataSeeder.php         Datos de demostración (solo en local)
│
├── docs/                              Esta documentación
│
├── resources/views/
│   ├── components/                    KIT DE UI REUTILIZABLE
│   │   ├── badge · card · stat · progress · empty-state · icon
│   │   ├── company-logo             logo de la empresa, con iniciales de reserva
│   │   ├── btn · input · select · textarea · field · page-header
│   │   ├── modal-panel · toasts
│   │   ├── layouts/admin.blade.php    Panel: barra lateral, notificaciones
│   │   ├── layouts/portal.blade.php   Portal: limpio, sin ruido, móvil primero
│   │   └── portal/                    tab · ring (anillo de avance) · nav
│   ├── livewire/                      Una vista por componente, misma jerarquía que app/Livewire
│   ├── pdf/quote.blade.php            Plantilla del presupuesto en PDF
│   └── portal/expired.blade.php       Enlace caducado o revocado
│
├── routes/
│   ├── web.php                        Panel (/panel/*), autenticado
│   ├── portal.php                     Portal (/portal/*), por token
│   └── auth.php                       Breeze
│
├── storage/app/documents/             Disco PRIVADO: documentos y fotos
│
└── tests/
    ├── Unit/QuoteCalculatorTest.php
    └── Feature/                       Workflow, portal, avance, asignación, extras, humo del panel
```

## Dónde añadir cosas nuevas

| Quiero… | Va en… |
|---------|--------|
| Un estado o categoría nueva | `app/Enums/` + migración si cambia una columna |
| Una regla de negocio | `app/Services/<Módulo>/` y un test en `tests/Feature/` |
| Una pantalla del panel | `app/Livewire/Admin/<Módulo>/` + vista + ruta en `web.php` |
| Una pantalla del portal | `app/Livewire/Portal/` + ruta en `portal.php` con su `portal.can:` |
| Un componente visual reutilizable | `resources/views/components/` |
| Un elemento de marca (logo, color) | `LogoService` + `x-company-logo`, y la clave en `settings` |
| Un aviso por email | `app/Notifications/<Destinatario>/` y envío vía `NotificationDispatcher` |
| Una tarea periódica | `app/Console/Commands/` + registro en `bootstrap/app.php` |
