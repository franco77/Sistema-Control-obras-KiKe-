# Arquitectura

Escrito para el equipo de desarrollo que mantiene y amplía este CRM.

---

## 1. Principio rector

El sistema es un **monolito modular**: un solo despliegue, pero con fronteras
claras entre módulos y con la lógica de negocio fuera de los componentes de
interfaz. Livewire orquesta la pantalla; los servicios deciden.

```
Ruta  ──►  Componente Livewire  ──►  Servicio de dominio  ──►  Modelo Eloquent
           (estado de pantalla)      (reglas de negocio)       (persistencia)
                   │
                   └──►  Policy  (¿puede este usuario?)
```

Tres reglas que se respetan en todo el código:

1. **Ningún componente Livewire cambia un estado de negocio directamente.**
   Enviar un presupuesto, aprobarlo, convertirlo en obra o asignar un proveedor
   pasa siempre por un servicio. Así la misma regla vale desde la interfaz, desde
   un comando de consola, desde una API futura o desde un test.

2. **Los importes y los porcentajes nunca se teclean.** `QuoteCalculator` y
   `ProjectProgressService` son la única fuente de verdad. Si un número está en la
   base de datos, lo ha puesto un servicio.

3. **Lo que ve el cliente se decide con una bandera explícita.** Fases, tareas,
   incidencias, fotos y documentos llevan `visible_to_client`. Por defecto, lo
   interno no sale.

---

## 2. Capas

### `app/Enums/` — vocabulario del dominio
22 enums respaldados por string con `label()` y `color()`. Se castean en los
modelos, de modo que `$quote->status` es un objeto, no una cadena suelta. Evitan
constantes mágicas repartidas por vistas y consultas.

### `app/Models/` — Eloquent
Modelos delgados: relaciones, casts, scopes reutilizables (`search`, `open`,
`delayed`, `visibleToClient`) y accesores de presentación. Cuatro traits
compartidos:

| Trait | Qué aporta |
|-------|------------|
| `HasSequentialCode` | Código legible y correlativo por año (`OBR-2026-0007`), generado bajo transacción con bloqueo para evitar colisiones |
| `RecordsActivity`   | `recordActivity()` con causante, IP y user-agent → tabla `activities` |
| `HasDocuments`      | Documentación adjunta polimórfica, con caducidades |
| `HasPortalTokens`   | Enlaces de portal asociados al recurso |

Los defaults viven **también en el modelo** (`$attributes`), no solo en la base de
datos: un objeto recién creado ya es coherente antes de refrescarlo.

### `app/Services/` — reglas de negocio

| Servicio | Responsabilidad |
|----------|-----------------|
| `Quotes\QuoteCalculator` | Recalcula partida → capítulo → documento: descuentos, IVA, coste y margen |
| `Quotes\QuoteWorkflow` | Máquina de estados del presupuesto; transiciones inválidas lanzan `InvalidTransitionException` |
| `Quotes\QuoteVersionService` | Clona el árbol completo en una versión nueva y revoca los enlaces de la anterior |
| `Quotes\QuoteConversionService` | Presupuesto aprobado → obra: capítulo = fase, partida = tarea, peso = importe |
| `Quotes\QuoteNumberGenerator` | Numeración anual correlativa |
| `Projects\ProjectProgressService` | Avance de fase (tareas completadas) y de obra (media ponderada por peso) |
| `Projects\ProjectCostService` | Coste real, extras aprobados, margen y desviación |
| `Projects\TaskAssignmentService` | Asignación con validación documental, reserva de calendario y aviso al proveedor |
| `Projects\ExtraWorkflow` | Ciclo de aprobación de extras; al aprobar amplía importe y plazo |
| `Portal\PortalTokenService` | Emisión y validación de tokens de acceso sin login |
| `Documents\DocumentService` | Subida a disco privado, hash SHA-256, caducidades |
| `Documents\PhotoService` | Redimensionado, miniatura y servido controlado |
| `Notifications\NotificationDispatcher` | Salida única de comunicaciones, con traza en `message_logs` |

### `app/Livewire/` — interfaz
Componentes de página completa para cada ruta y componentes anidados
reutilizables (`DocumentManager` se monta sobre cualquier modelo con
`HasDocuments`; `PhaseManager`, `IncidentManager`, `PhotoManager`,
`ExtraManager`, `UpdateComposer` componen la ficha de obra).

Traits compartidos: `WithToasts` (avisos), `WithSorting` (ordenación con estado en
la URL), `InteractsWithPortal` (resolución seguraidad del token de portal).

### `app/Policies/` — autorización
Auto-descubiertas por Laravel 11. `Gate::before` da acceso total al rol `admin`;
el resto pasa por permisos concretos de Spatie. Dos policies tienen reglas propias
de negocio: `QuotePolicy::update()` exige que el presupuesto siga en borrador, y
`ProjectPolicy::view()` deja entrar siempre al jefe de obra asignado.

---

## 3. Modelo de datos

37 tablas. Los bloques principales:

```
clients ──┬── client_contacts
          ├── properties ────────┐
          ├── quotes ──┬── quote_sections ── quote_items
          │            └── (project_id)     │
          └── conversations ── conversation_messages
                                            │
providers ┬── provider_trade ── trades ─────┤
          ├── provider_availabilities       │
          ├── provider_reviews              │
          └── documents (polimórfico)       │
                                            ▼
projects ─┬── project_phases ── project_tasks ── (provider_id)
          ├── project_incidents
          ├── project_photos
          ├── project_extras
          ├── project_updates
          ├── project_provider
          ├── calendar_events
          └── portal_access_tokens (polimórfico)

catalog_categories ── catalog_items       activities (auditoría polimórfica)
settings                                   message_logs
```

**Decisiones que conviene conocer:**

- `quotes.number` **no** es único por sí solo: la unicidad es `(number, version)`,
  porque las versiones de un presupuesto comparten número.
- `quotes.project_id` y `projects.quote_id` se referencian mutuamente. La clave
  foránea circular se cierra en una migración posterior
  (`add_project_foreign_keys`).
- Los importes son `decimal`, nunca `float`. Los cálculos se hacen en PHP y se
  redondean a 2 decimales antes de persistir.
- `activities` es una tabla de auditoría polimórfica con eventos de negocio
  legibles (`quote.sent`, `extra.approved`), no un diff automático de columnas.
- `Relation::enforceMorphMap()` fija alias cortos y estables para las columnas
  polimórficas: renombrar o mover una clase no rompe los datos guardados.

---

## 4. Flujo administrador ↔ cliente

```
ADMIN                                          CLIENTE
─────                                          ───────
Alta de cliente + inmueble
        │
Presupuesto (constructor de partidas)
   capítulos · partidas · catálogo
   coste interno · margen en vivo
        │
   [Enviar al cliente] ──────────────► email con enlace firmado
        │                                      │
   estado: Enviado                      /portal/t/{token}
                                               │ canje por sesión,
                                               │ el token sale de la URL
                                               ▼
                                        Ver presupuesto, PDF,
                                        activar partidas opcionales
        │◄──── estado: Visto ──────────────────┤
        │                                      │
        │◄──── Aprobado / Rechazado ───────────┤ firma con nombre + IP
        │
   [Convertir en obra]
   capítulo → fase, partida → tarea
   peso de fase = peso económico
        │
   Asignar proveedores  ───────────────► email al proveedor
   (valida documentación legal,          /portal/proveedor
    reserva su calendario)               marca avances, sube fotos
        │
   Ejecución: tareas, fotos,
   incidencias, partes de obra
        │                                      ┌─────────────────────┐
        └──────────────────────────────────►   │ PORTAL DEL CLIENTE  │
                                               │ avance y fases      │
   Surge un imprevisto                         │ fotos               │
        │                                      │ tareas hechas       │
   [Crear extra] → [Enviar] ──────────────►    │ incidencias         │
        │                                      │ documentos          │
        │◄──── Aprobado / Rechazado ───────────│ mensajes            │
        │      (suma al contratado,            │ APROBAR EXTRAS      │
        │       amplía el plazo)               └─────────────────────┘
        │
   [Finalizar obra] ──────────────────► email de entrega + garantía
```

---

## 5. Seguridad del portal sin login

Es la superficie más expuesta del sistema y el diseño lo refleja:

1. **El token en claro solo existe en el enlace del email.** En base de datos se
   guarda su hash SHA-256. Un volcado de la tabla no permite entrar a ningún sitio.

2. **El enlace se canjea por una sesión y desaparece de la URL.**
   `/portal/t/{token}` valida, regenera la sesión y redirige a `/portal/obra`. El
   token deja de viajar en el historial del navegador, en los marcadores y en la
   cabecera `Referer`.

3. **Permisos acotados por enlace.** Cada token lleva sus `abilities`. El enlace de
   un presupuesto (`quote.view`, `quote.decide`) devuelve 403 en las rutas de obra;
   el de un extra concreto no abre el portal completo. Verificado con tests.

4. **Revalidación en cada petición, incluidas las de Livewire.** El middleware
   `StartPortalSession` comprueba vigencia, revocación y número de usos; el trait
   `InteractsWithPortal` repite la comprobación dentro de los componentes. El
   identificador del recurso **nunca** viaja como propiedad pública de Livewire,
   porque sería manipulable desde el navegador.

5. **Caducidad, revocación y contador de usos.** Crear una versión nueva de un
   presupuesto o anular un extra revoca sus enlaces al instante.

6. **Ficheros bajo control de acceso.** Documentos y fotos se sirven por
   controlador, que comprueba `visible_to_client` y que el recurso pertenezca de
   verdad al token presentado.

---

## 6. Convenciones

- Tipado estricto (`declare(strict_types=1)`) en todo `app/`.
- Validación en objetos de formulario (`app/Livewire/Forms/`) o en `rules()` del
  componente; nunca en la vista.
- Rutas en castellano (`/panel/presupuestos`), código en inglés.
- Los comentarios explican **por qué**, no qué hace la línea siguiente.
- `Model::preventLazyLoading()` está activo fuera de producción: un N+1 rompe el
  test en vez de llegar a producción disfrazado de lentitud.

---

## 7. Dos trampas que ya nos han mordido

### Alpine solo puede haber uno
Livewire 3 empaqueta y arranca su propio Alpine. Breeze, por defecto, arranca
otro en `resources/js/app.js`. Con dos instancias, la primera reclama el DOM y
**las directivas `wire:` no se enlazan nunca**: los botones no hacen nada, sin
error en pantalla ni en los logs.

`resources/js/app.js` **no debe importar ni arrancar Alpine**, y todo layout que
use directivas `wire:` o `x-data` debe incluir `@livewireScripts`.
`tests/Feature/FrontendSetupTest.php` lo vigila.

### Recalcular sobre relaciones ya cargadas
`QuoteCalculator` y `ProjectProgressService` usan `load()`, nunca `loadMissing()`.
Quien los llama acaba de modificar una partida o una tarea con una consulta
directa (`$quote->items()->find($id)->update(...)`), de modo que la colección que
ya estaba en memoria está obsoleta. Con `loadMissing()` el recálculo se hacía
sobre los valores antiguos y el total salía mal **sin lanzar ningún error**.

Regla: si un servicio recalcula, refresca. No confíes en lo que traiga el
llamante.
