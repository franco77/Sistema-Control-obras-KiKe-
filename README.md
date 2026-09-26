# Sistema de Control de Obras

CRM para empresas de reformas: clientes, inmuebles, proveedores autónomos, presupuestos
versionados, ejecución de obra y **portal del cliente sin contraseña**.

<p>
  <img alt="PHP 8.3" src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white">
  <img alt="Laravel 11" src="https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white">
  <img alt="Livewire 3" src="https://img.shields.io/badge/Livewire-3-4E56A6?logo=livewire&logoColor=white">
  <img alt="Tailwind CSS 3" src="https://img.shields.io/badge/Tailwind-3-06B6D4?logo=tailwindcss&logoColor=white">
  <img alt="MariaDB" src="https://img.shields.io/badge/MariaDB%20%2F%20MySQL-8-003545?logo=mariadb&logoColor=white">
  <img alt="Tests" src="https://img.shields.io/badge/tests-187%20passing-2ea44f">
</p>

---

## Índice

- [El problema que resuelve](#el-problema-que-resuelve)
- [Flujo completo: administrador ↔ cliente](#flujo-completo-administrador--cliente)
- [Módulos](#módulos)
- [Seguridad del portal sin login](#seguridad-del-portal-sin-login)
- [Arquitectura](#arquitectura)
- [Stack y requisitos](#stack-y-requisitos)
- [Instalación](#instalación)
- [Usuarios de prueba](#usuarios-de-prueba)
- [Procesos en segundo plano](#procesos-en-segundo-plano)
- [Tests](#tests)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Antes de pasar a producción](#antes-de-pasar-a-producción)
- [Documentación](#documentación)
- [Licencia](#licencia)

---

## El problema que resuelve

Una reformista pequeña o mediana pierde tiempo y dinero en tres sitios concretos:

1. **Presupuestar.** Cada propuesta se rehace desde cero en una hoja de cálculo, sin
   saber el margen real hasta que la obra termina.
2. **Explicar cómo va la obra.** El cliente llama cada dos días. Cada llamada son
   quince minutos del jefe de obra.
3. **Justificar los extras.** Lo que se acordó de palabra se discute al facturar.

Este sistema ataca los tres:

- Un **banco de precios propio** con coste interno y PVP, y el margen calculado en vivo
  mientras presupuestas, con aviso si baja del 12 %.
- Un **portal del cliente** con avance real, fotos, tareas hechas y pendientes: el
  cliente mira en vez de llamar.
- **Extras aprobados por escrito** desde ese mismo portal, con nombre, fecha e IP.
  Hasta que no los aprueba, no se ejecutan.

---

## Flujo completo: administrador ↔ cliente

```
ADMINISTRADOR                                  CLIENTE
─────────────                                  ───────
Alta de cliente + inmueble
        │
Presupuesto (constructor de partidas)
   capítulos · partidas · catálogo
   coste interno · margen en vivo
        │
   [Enviar al cliente] ──────────────►  email con enlace firmado
        │                                       │
   estado: Enviado                       /portal/t/{token}
                                                │ se canjea por sesión y
                                                │ el token sale de la URL
                                                ▼
                                         Ver presupuesto, descargar PDF,
                                         activar partidas opcionales
        │◄──── estado: Visto ───────────────────┤
        │◄──── Aprobado / Rechazado ────────────┤  firma con nombre + IP
        │
   [Convertir en obra]
   capítulo → fase, partida → tarea
   peso de fase = peso económico
        │
   Asignar proveedores ────────────────►  email al autónomo
   (valida seguro, PRL y RETA;             /portal/proveedor
    reserva su calendario)                 marca avances, sube fotos
        │
   Ejecución: tareas, fotos,                ┌──────────────────────┐
   incidencias, partes de obra  ─────────►  │  PORTAL DEL CLIENTE  │
        │                                   │  avance y fases      │
   Surge un imprevisto                      │  fotos               │
        │                                   │  tareas completadas  │
   [Crear extra] → [Enviar] ─────────────►  │  incidencias         │
        │                                   │  documentos          │
        │◄──── Aprobado / Rechazado ─────────│  mensajes           │
        │      suma al contratado y          │  APROBAR EXTRAS     │
        │      amplía el plazo               └──────────────────────┘
        │
   [Finalizar obra] ───────────────────►  email de entrega + garantía
```

---

## Módulos

### 1 · Clientes e inmuebles
- Particulares, empresas y comunidades de propietarios, con código correlativo (`CLI-2026-0001`).
- **Inmuebles** con superficie, año, ascensor y **notas de acceso** (portero, llaves,
  horarios permitidos): lo que evita media docena de llamadas por obra.
- Contactos múltiples, con contacto principal y control de quién recibe avisos.
- Documentación adjunta con fechas de caducidad.
- Ficha 360º: inmuebles, presupuestos, obras, documentos y traza de actividad.

### 2 · Proveedores (autónomos)
- Autónomos, empresas subcontratadas y personal propio.
- **Oficios múltiples con tarifa y experiencia por oficio** — un fontanero que también
  pinta no cobra lo mismo por cada cosa.
- **Documentación legal obligatoria**: seguro de RC, PRL y alta de autónomo. Sin ella
  en vigor, el sistema **impide asignarle tareas**. No es un aviso: es un bloqueo.
- Disponibilidad: vacaciones y bajas, más reservas automáticas al asignarle obra.
- Histórico de trabajos y valoraciones (calidad, puntualidad, limpieza, trato).

### 3 · Presupuestos
- Constructor con capítulos ordenables y partidas en blanco o tomadas del catálogo.
- Descuentos por línea y globales (porcentaje o importe), IVA configurable.
- **Margen visible mientras presupuestas**, con alerta por debajo del 12 %.
- **Partidas opcionales** que el cliente activa o desactiva desde su portal,
  recalculando el total al instante.
- **Control de versiones**: una versión enviada no se edita. Se clona a una nueva en
  borrador, la anterior queda como sustituida y sus enlaces se revocan.
- PDF profesional con logo, numeración de páginas y bloque de firma.
- Seguimiento de aperturas: cuántas veces y cuándo lo ha abierto el cliente.

### 4 · Obras
- **Conversión automática** del presupuesto aprobado: capítulo → fase, partida → tarea.
- El **peso de cada fase se deriva de su importe**, de modo que el porcentaje que ve el
  cliente refleja el avance económico real, no el número de casillas marcadas.
- Fases y tareas con estados, prioridades, fechas, horas y coste.
- **Incidencias** con gravedad, impacto en coste y plazo, y resolución.
- **Reportaje fotográfico** por etapas: antes, avance, después, incidencia, detalle.
- **Extras aprobables** que al aceptarse suman al contratado y amplían el plazo.
- Diario de obra publicable en el portal.
- Tablero por estado y control de rentabilidad: contratado, coste, margen y desviación.

### 5 · Agenda
- Vista mensual y de lista: visitas, hitos, tareas programadas, entregas, inspecciones.
- Las **ausencias de proveedores aparecen superpuestas** en la misma rejilla, así que se
  ve de un golpe quién no está disponible.
- Recordatorios automáticos cada quince minutos.

### 6 · Notificaciones
- **Al cliente**: presupuesto listo, extra por aprobar, avances, incidencias, entrega.
- **Al proveedor**: tarea asignada, documentación por caducar.
- **Internas**: decisiones del cliente, obras retrasadas, mensajes, recordatorios.
- Toda comunicación saliente queda registrada en `message_logs`, con destinatario,
  asunto y estado. Imprescindible cuando se discute un plazo o una aprobación.

### 7 · Portal del cliente
Sin contraseña, por enlace firmado:
- Anillo de avance, fases con su progreso, novedades y próximas citas.
- Trabajos hechos y pendientes, galería con visor, incidencias comunicadas.
- **Aprobación de extras** con firma por nombre e IP.
- Documentos descargables y mensajería con el equipo.

Y un portal paralelo para **proveedores**: sus tareas, cambio de estado y subida de
fotos. Nunca ven importes de venta ni datos de otros proveedores.

---

## Seguridad del portal sin login

Es la superficie más expuesta del sistema y el diseño lo refleja.

| Medida | Por qué |
|---|---|
| Solo se guarda el **hash SHA-256** del token | Un volcado de la base de datos no permite entrar a ningún sitio |
| El enlace **se canjea por sesión** y desaparece de la URL | El token deja de viajar en el historial, en los marcadores y en la cabecera `Referer` |
| **Permisos acotados por enlace** (`abilities`) | El enlace de un presupuesto devuelve 403 en las rutas de obra; el de un extra concreto no abre el portal completo |
| **Revalidación en cada petición**, incluidas las de Livewire | Vigencia, revocación y número de usos se comprueban siempre; el id del recurso nunca viaja como propiedad pública |
| **Caducidad, revocación y contador de usos** | Versionar un presupuesto o anular un extra revoca sus enlaces al instante |
| **Ficheros bajo control de acceso** | Documentos y fotos se sirven por controlador, que valida `visible_to_client` y que el recurso pertenezca al token presentado |

Todo lo anterior está cubierto por tests, incluido el caso de revocar un enlace con
sesión ya abierta.

---

## Arquitectura

Monolito modular: un solo despliegue, fronteras claras y la lógica de negocio **fuera**
de los componentes de interfaz.

```
Ruta  ──►  Componente Livewire  ──►  Servicio de dominio  ──►  Modelo Eloquent
           (estado de pantalla)      (reglas de negocio)       (persistencia)
                   │
                   └──►  Policy  (¿puede este usuario?)
```

Tres reglas que se respetan en todo el código:

1. **Ningún componente Livewire cambia un estado de negocio directamente.** Enviar un
   presupuesto, aprobarlo, convertirlo en obra o asignar un proveedor pasa siempre por un
   servicio. Así la misma regla vale desde la interfaz, desde consola, desde una API
   futura y desde los tests.
2. **Los importes y los porcentajes nunca se teclean.** `QuoteCalculator` y
   `ProjectProgressService` son la única fuente de verdad.
3. **Lo que ve el cliente se decide con una bandera explícita** (`visible_to_client`).
   Por defecto, lo interno no sale.

### En números

| | | | |
|---|---|---|---|
| **45** tablas | **29** modelos | **22** enums de dominio | **14** servicios |
| **38** componentes Livewire | **88** vistas Blade | **13** notificaciones | **9** policies |
| **37** migraciones | **4** tareas programadas | **187** tests | ~**11.400** líneas en `app/` |

### Servicios de dominio

| Servicio | Responsabilidad |
|---|---|
| `Quotes\QuoteCalculator` | Recalcula partida → capítulo → documento: descuentos, IVA, coste y margen |
| `Quotes\QuoteWorkflow` | Máquina de estados; las transiciones inválidas lanzan excepción |
| `Quotes\QuoteVersionService` | Clona el árbol completo y revoca los enlaces de la versión anterior |
| `Quotes\QuoteConversionService` | Presupuesto aprobado → obra, con peso de fase por importe |
| `Projects\ProjectProgressService` | Avance de fase y de obra, ponderado |
| `Projects\ProjectCostService` | Coste real, extras aprobados, margen y desviación |
| `Projects\TaskAssignmentService` | Asignación con validación documental y reserva de calendario |
| `Projects\ExtraWorkflow` | Aprobación de extras; al aceptar amplía importe y plazo |
| `Portal\PortalTokenService` | Emisión y validación de los tokens de acceso sin login |
| `Documents\DocumentService` | Subida a disco privado, hash SHA-256, caducidades |
| `Documents\PhotoService` | Redimensionado, miniatura y servido controlado |
| `Branding\LogoService` | Logo de la empresa: subida, normalizado y consumo |
| `Notifications\NotificationDispatcher` | Salida única de comunicaciones, con traza |

---

## Stack y requisitos

- **PHP** 8.3 o superior, con extensiones `gd`, `pdo_mysql`, `mbstring`, `zip`
- **Laravel** 11 · **Livewire** 3 · **Tailwind CSS** 3 · **Alpine** (el que trae Livewire)
- **MariaDB** 10.6+ o **MySQL** 8
- **Composer** 2 · **Node** 20+
- Paquetes: `spatie/laravel-permission`, `barryvdh/laravel-dompdf`, `intervention/image`

---

## Instalación

```bash
git clone https://github.com/franco77/Sistema-Control-obras-KiKe-.git
cd Sistema-Control-obras-KiKe-

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Crea la base de datos y ajusta `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crm_reformas
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed
php artisan storage:link      # necesario para el logo de la empresa
npm run build                 # o `npm run dev` en desarrollo
php artisan serve
```

Abre **http://127.0.0.1:8000/panel**.

En entorno `local` el seeder incluye datos de demostración: dos clientes con inmuebles,
cuatro proveedores con documentación en vigor, un presupuesto aprobado ya convertido en
obra con avance real, un presupuesto esperando respuesta, una incidencia comunicada y un
extra pendiente de aprobación.

> [!IMPORTANT]
> `resources/js/app.js` **no debe importar ni arrancar Alpine**. Livewire 3 trae el suyo,
> y dos instancias de Alpine rompen el enlace de las directivas `wire:`: los botones
> dejan de responder sin error alguno en pantalla ni en los logs. Hay un test que lo
> vigila (`FrontendSetupTest`).

---

## Usuarios de prueba

| Email | Rol | Contraseña |
|---|---|---|
| `admin@crm.test` | admin | `password` |
| `obra@crm.test` | jefe de obra | `password` |
| `comercial@crm.test` | comercial | `password` |
| `admin.oficina@crm.test` | administrativo | `password` |

> [!WARNING]
> Son credenciales de demostración. **Cámbialas antes de exponer la aplicación**, y borra
> los usuarios que no uses.

### Cómo entrar como cliente

El portal no tiene login: se accede por enlace. Genéralo desde el panel:

- **Obra** → botón «Enlace del cliente» en la cabecera de la ficha
- **Presupuesto** → «Regenerar enlace», o directamente «Enviar al cliente»
- **Proveedor** → «Enlace de portal» en su ficha

El enlace **solo se muestra una vez** (en base de datos queda el hash). Ábrelo en una
ventana de incógnito: entrar al portal invalida la sesión del navegador, así que en la
misma ventana te sacaría del panel.

---

## Procesos en segundo plano

```bash
php artisan queue:work        # notificaciones (ShouldQueue)
php artisan schedule:work     # tareas periódicas
```

En producción basta un cron para el planificador:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

| Comando | Cuándo | Qué hace |
|---|---|---|
| `crm:expire-quotes` | diario 01:00 | Caduca presupuestos vencidos sin respuesta |
| `crm:notify-expiring-documents` | diario 07:30 | Avisa de seguros, PRL y RETA por caducar |
| `crm:notify-delayed-projects` | laborables 08:00 | Alerta de obras fuera de plazo |
| `crm:send-event-reminders` | cada 15 min | Recordatorios de agenda |

---

## Tests

```bash
php artisan test
```

Requiere una base de datos `crm_reformas_test` (configurada en `phpunit.xml`).

**187 tests, 422 aserciones.** Cubren el cálculo de presupuestos, la máquina de estados,
el versionado, la conversión a obra, el avance ponderado, la asignación de proveedores, la
aprobación de extras, la subida del logo y la seguridad del portal.

Además hay tres **guards de arquitectura**, nacidos de fallos reales que no daban ningún
error visible:

| Guard | Qué evita |
|---|---|
| `FrontendSetupTest` | Que alguien vuelva a arrancar un segundo Alpine y deje todos los botones muertos |
| `PagesWithDataTest` | Que una pantalla pase el test con tablas vacías y reviente con datos reales (violaciones de lazy loading) |
| `ModelBindingsTest` | Que un `wire:model` apunte a una propiedad inexistente: el campo se pinta vacío y lo que escribe el usuario se descarta sin avisar |

---

## Estructura del proyecto

```
app/
├── Enums/          22 enums de dominio con label() y color()
├── Models/         Eloquent delgado + 4 traits compartidos
├── Services/       ◄── LA LÓGICA DE NEGOCIO VIVE AQUÍ
├── Livewire/
│   ├── Admin/      Una carpeta por módulo del panel
│   └── Portal/     Cara del cliente y del proveedor
├── Notifications/  Client/ · Provider/ · Internal/
├── Policies/       Autorización por modelo
├── Http/           Solo lo que no es Livewire: ficheros, PDF, entrada al portal
└── Console/        Tareas programadas

resources/views/
├── components/     Kit de UI reutilizable (badge, card, stat, btn, field…)
├── livewire/       Una vista por componente, misma jerarquía que app/Livewire
└── pdf/            Plantilla del presupuesto

routes/
├── web.php         Panel (/panel/*), autenticado
└── portal.php      Portal (/portal/*), por token
```

Detalle completo en [docs/ESTRUCTURA.md](docs/ESTRUCTURA.md).

---

## Antes de pasar a producción

- [ ] **Correo real**: SMTP con dominio verificado (SPF/DKIM). El presupuesto llega por
      email; ahí se juega la conversión.
- [ ] **Colas y planificador** bajo Supervisor y cron. Sin esto no sale ningún aviso.
- [ ] **Backups** de base de datos **y** de `storage/app/documents`. Las fotos de obra no
      están en ningún otro sitio.
- [ ] **HTTPS obligatorio**. Los enlaces del portal viajan por correo.
- [ ] **Datos reales de la empresa** en `/panel/configuracion`: CIF, dirección, IVA,
      condiciones de pago y logo. Aparecen en cada PDF.
- [ ] **Catálogo propio**: las 33 partidas de ejemplo son un punto de partida; ajusta
      costes y precios reales.
- [ ] **Cambiar las credenciales de demostración** y `APP_DEBUG=false`.
- [ ] **RGPD**: registro de tratamientos, política de privacidad, plazos de conservación
      y procedimiento de borrado. Se manejan datos personales de clientes y autónomos.
- [ ] **Monitorización** de errores y alerta si la cola se atasca.
- [ ] **Probar una obra real de principio a fin** antes de migrar el histórico.

---

## Documentación

| Documento | Contenido |
|---|---|
| [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md) | Capas, modelo de datos, flujos, decisiones y trampas conocidas |
| [docs/ESTRUCTURA.md](docs/ESTRUCTURA.md) | Estructura de carpetas y dónde añadir cada cosa |
| [docs/ROADMAP.md](docs/ROADMAP.md) | Plan de desarrollo por fases y criterios de aceptación |
| [docs/MEJORAS-FUTURAS.md](docs/MEJORAS-FUTURAS.md) | Backlog: facturación y Verifactu, Gantt, WhatsApp, PWA de obra… |

---

## Licencia

Software propietario. Todos los derechos reservados.
El código de terceros conserva su licencia original (Laravel y sus dependencias, MIT).
