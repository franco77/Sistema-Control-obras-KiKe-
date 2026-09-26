# Roadmap de desarrollo

Escrito para ti y para quien se incorpore al proyecto.

Las **fases 0 a 6 ya están implementadas y probadas** en este repositorio. Se
documentan igualmente porque marcan el orden en que conviene revisarlas,
validarlas con usuarios reales y, si hace falta, rehacer alguna decisión.

---

## Fase 0 · Cimientos ✅

- Laravel 11 + Livewire 3 + Tailwind + Breeze.
- MariaDB/MySQL, disco privado `documents`, colas y planificador.
- 22 enums de dominio, kit de componentes Blade, dos layouts (panel y portal).
- Roles y permisos: admin, jefe de obra, comercial, administrativo.

**Verificación:** `php artisan test` en verde y el panel carga con datos de demo.

---

## Fase 1 · Clientes e inmuebles ✅

- CRUD de clientes con búsqueda, filtros y ordenación en la URL.
- Inmuebles con superficie, año, ascensor y **notas de acceso** (portero, llaves,
  horarios permitidos): lo que evita media docena de llamadas por obra.
- Contactos múltiples, con contacto principal y quién recibe avisos.
- Documentación adjunta con caducidades.
- Ficha 360º: inmuebles, presupuestos, obras, documentos y traza de actividad.

**Criterio de aceptación:** dar de alta un cliente con dos inmuebles y presupuestar
desde la propia ficha sin pasar por otro menú.

---

## Fase 2 · Proveedores ✅

- Registro de autónomos, empresas y personal propio.
- Oficios múltiples con **tarifa y experiencia por oficio** (un fontanero que
  también pinta no cobra lo mismo por cada cosa).
- Documentación legal obligatoria: seguro de RC, PRL y alta de autónomo. Sin ella,
  el sistema **impide asignarle tareas**.
- Disponibilidad: vacaciones y bajas, más reservas automáticas al asignarle obra.
- Histórico de trabajos y valoraciones (calidad, puntualidad, limpieza, trato).

**Criterio de aceptación:** intentar asignar un proveedor con el seguro caducado y
recibir un error claro, no un fallo silencioso.

---

## Fase 3 · Catálogo y presupuestos ✅

- Banco de precios propio con coste interno y PVP, y margen calculado por partida.
- Constructor de presupuestos: capítulos ordenables, partidas en blanco o desde
  catálogo, descuentos por línea y globales, IVA configurable.
- **Margen visible en vivo mientras presupuestas**, con aviso por debajo del 12 %.
- Partidas opcionales que el cliente activa o desactiva desde su portal,
  recalculando el total.
- Control de versiones: una versión enviada no se edita; se clona a una nueva y la
  anterior queda como sustituida con sus enlaces revocados.
- PDF profesional con numeración de páginas y bloque de firma.
- Envío por email con enlace seguro; seguimiento de aperturas.

**Criterio de aceptación:** enviar un presupuesto, abrirlo como cliente en el móvil,
aprobarlo y ver la firma con fecha e IP en el panel.

---

## Fase 4 · Obras ✅

- Conversión automática presupuesto → obra: capítulo = fase, partida = tarea, y el
  **peso de cada fase se deriva de su importe**, de forma que el porcentaje que ve
  el cliente refleja el avance económico real, no el número de casillas marcadas.
- Fases y tareas con estados, prioridades, fechas, horas y coste.
- Asignación de proveedores con validación documental y reserva de calendario.
- Incidencias con gravedad, impacto en coste y plazo, y resolución.
- Reportaje fotográfico por etapas (antes, avance, después, incidencia, detalle).
- Extras aprobables por el cliente: al aprobarse suman al contratado y amplían el
  plazo automáticamente.
- Diario de obra publicable en el portal.
- Tablero por estado y control de rentabilidad (contratado, coste, margen, desvío).

**Criterio de aceptación:** marcar tareas como completadas y ver cómo se mueve el
porcentaje de la fase y el de la obra, ponderado.

---

## Fase 5 · Agenda y comunicación ✅

- Calendario mensual y en lista: visitas, hitos, tareas programadas, entregas.
- Las **ausencias de proveedores aparecen superpuestas** en la misma rejilla.
- Recordatorios automáticos cada 15 minutos.
- Notificaciones al cliente (presupuesto, extras, avances, incidencias, entrega),
  al proveedor (tarea asignada, documentación por caducar) e internas.
- Toda comunicación saliente queda registrada en `message_logs`.
- Hilos de mensajes entre cliente y equipo, ligados a la obra.

**Criterio de aceptación:** un correo enviado al cliente aparece en el log con su
destinatario, asunto y estado.

---

## Fase 6 · Portal del cliente ✅

- Acceso por enlace firmado, sin contraseña. El token se canjea por sesión y
  desaparece de la URL.
- Anillo de avance, fases con su progreso, novedades, próximas citas.
- Trabajos hechos y pendientes, galería con visor, incidencias comunicadas.
- Aprobación de extras con firma por nombre e IP.
- Documentos descargables y mensajería.
- Portal paralelo para proveedores: sus tareas, cambio de estado y subida de fotos.

**Criterio de aceptación:** el enlace de un presupuesto devuelve 403 en las rutas de
obra, y revocarlo expulsa a quien ya tuviera sesión abierta.

---

## Fase 7 · Antes de producción ⏳

Esto es lo que queda por hacer y conviene no saltárselo.

1. **Correo real.** Configurar el proveedor SMTP (Postmark, SES, Resend), dominio
   verificado con SPF/DKIM y remitente propio. Probar con buzones reales, porque el
   presupuesto llega por email y ahí se juega la conversión.
2. **Colas en supervisor.** `queue:work` bajo Supervisor o un worker gestionado, y
   `schedule:run` en cron. Sin esto no salen los avisos.
3. **Backups.** `spatie/laravel-backup` con base de datos **y** `storage/app/documents`
   fuera del servidor. Las fotos de obra no están en ningún otro sitio.
4. **HTTPS obligatorio y cabeceras de seguridad.** Los enlaces del portal viajan por
   correo; sin TLS no hay nada que discutir.
5. **Datos reales de la empresa.** Rellenar `/panel/configuracion`: CIF, dirección,
   IVA, condiciones de pago y condiciones generales. Aparecen en cada PDF.
6. **Catálogo propio.** Las 33 partidas de ejemplo son un punto de partida; hay que
   ajustar costes y precios reales antes de presupuestar en serio.
7. **Logo y marca** en layouts y PDF.
8. **RGPD.** Registro de actividades de tratamiento, política de privacidad, tiempos
   de conservación y procedimiento de borrado. Se gestionan datos personales de
   clientes y autónomos.
9. **Monitorización.** Sentry o Flare para errores, y alerta si la cola se atasca.
10. **Prueba con una obra real de principio a fin** antes de migrar el histórico.

---

## Fase 8 · Primeras semanas en uso

Lo que casi siempre pide el usuario tras el primer mes:

- Ajustar textos de los correos al tono de la empresa.
- Añadir partidas al catálogo sobre la marcha (hoy se hace en `/panel/catalogo`).
- Plantillas de presupuesto por tipo de reforma (baño completo, cocina, integral).
- Exportar listados a Excel.
- Firma manuscrita en el portal, si el cliente la pide.

Conviene **no** construir nada de la lista de mejoras futuras hasta haber cerrado
tres obras completas con el sistema. Lo que parece imprescindible en el papel
resulta prescindible en la obra, y al revés.
