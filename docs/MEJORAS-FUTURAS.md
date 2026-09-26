# Mejoras futuras

Backlog ordenado por relación valor / esfuerzo. Nada de esto está implementado.

---

## Alto valor, esfuerzo contenido

### 1. Facturación y certificaciones
Series de factura, IVA y retención de IRPF de autónomos, facturas de cliente
enlazadas a hitos de pago del presupuesto, y certificaciones mensuales de obra
ejecutada. Es la ampliación natural: el sistema ya conoce importes, extras y
avance, solo falta emitir.
→ Añadir `invoices`, `invoice_lines`, `payments`. Vigilar **Verifactu**: desde
2026 la facturación en España exige registro inalterable y encadenado.

### 2. Plantillas de presupuesto
Partir de «baño completo 6 m²» o «reforma integral 90 m²» y ajustar cantidades.
Reduce de horas a minutos el presupuesto más habitual.
→ Marcar presupuestos como plantilla y clonar con `QuoteVersionService::duplicate()`.

### 3. Firma manuscrita en el portal
Hoy se firma escribiendo el nombre, con IP y sello temporal. Añadir trazo sobre
canvas y guardarlo en `quotes.signature_path` (la columna ya existe).
Para valor probatorio serio, firma electrónica avanzada con un tercero.

### 4. WhatsApp
El canal real del sector. Avisos de visita, aprobación de extras y enlaces de
portal por WhatsApp Business API. La infraestructura ya está lista:
`message_logs` contempla el canal y `NotificationDispatcher` es el único punto de
salida.

### 5. App móvil para el jefe de obra
Hoy el panel es responsive, pero a pie de obra se necesita cámara directa, subida
en segundo plano y funcionamiento sin cobertura.
→ PWA con cola offline antes que una app nativa.

### 6. Exportación a Excel/CSV
Listados de clientes, presupuestos, obras y márgenes.
→ `maatwebsite/excel`.

---

## Valor medio

### 7. Planificación tipo Gantt
Diagrama de fases y tareas con dependencias (`la fontanería no empieza hasta que
acabe la demolición`), camino crítico y recálculo automático de fechas al
retrasarse una tarea.
→ Añadir `project_task_dependencies`.

### 8. Control de compras y materiales
Pedidos a proveedor, albaranes, recepción en obra y coste real de material frente
al presupuestado. Hoy el coste se imputa a nivel de tarea.

### 9. Partes de horas
Que el proveedor fiche entrada y salida desde su portal, con geolocalización
opcional, y que el coste real de la tarea se calcule solo.

### 10. Portal del cliente ampliado
Estado de pagos, calendario de hitos, valoración de la obra al cerrar y
solicitudes de garantía posventa.

### 11. Cuadro de mando económico
Margen por tipo de obra, por jefe de obra y por proveedor. Desviación media entre
presupuestado y ejecutado. Tasa de conversión por origen del cliente. Con estos
datos se corrigen precios de catálogo con fundamento.

### 12. Multi-empresa
Si el CRM se ofrece a otras reformistas: `company_id` con scope global,
subdominio por empresa y branding propio. Se decidió no hacerlo ahora
deliberadamente; añadirlo después es trabajo, pero acotado y mecánico.

---

## Valor a más largo plazo

### 13. Integración contable
Exportación a A3, Contasol o Holded para no teclear dos veces.

### 14. Automatizaciones
«Si un presupuesto lleva 5 días sin abrir, recordatorio automático.»
«Si una obra supera el 110 % del coste previsto, avisa a dirección.»
Reglas configurables sin tocar código.

### 15. Portal de licitación para proveedores
Publicar una fase, que varios autónomos oferten y comparar precio, plazo y
valoración histórica antes de adjudicar.

### 16. Asistente de presupuestos con IA
A partir de una descripción y unas fotos del estado actual, proponer capítulos y
partidas del catálogo con mediciones estimadas. El presupuestador revisa y ajusta.
Útil de verdad solo cuando el catálogo tenga histórico suficiente.

### 17. Reconocimiento de documentos
Leer facturas de proveedor en PDF y volcarlas al coste de la obra sin teclear.

### 18. Geolocalización y rutas
Mapa de obras activas y optimización de las visitas del jefe de obra.

---

## Deuda técnica conocida

Cosas que hoy funcionan pero conviene mejorar antes de que crezca el volumen:

| Tema | Situación actual | Qué hacer |
|------|------------------|-----------|
| Reordenar capítulos y partidas | Botones de subir/bajar | Arrastrar y soltar (`livewire-sortable`) |
| Búsquedas | `LIKE %término%` | Índices FULLTEXT o Meilisearch al pasar de ~50.000 registros |
| Fotos | Redimensionado síncrono al subir | Mover a cola; almacenamiento en S3 |
| Auditoría | Eventos de negocio en `activities` | Añadir diff de campos en cambios sensibles de importes |
| Tests de interfaz | Cobertura por HTTP y servicios | Añadir pruebas de componentes con `Livewire::test()` |
| Idiomas | Textos en castellano en las vistas | Extraer a ficheros de traducción si se opera fuera de España |
| Papelera | `SoftDeletes` sin interfaz | Pantalla de restauración de elementos borrados |
