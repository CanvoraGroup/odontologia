# Soporte multiempresa y trazabilidad

La clinica propietaria se representa con `odo_clinicas`. Una cuenta interna tiene `odo_usuarios.id_clinica`; el administrador de plataforma se identifica con `es_admin_plataforma = 1` y no recibe acceso clinico global. La empresa siempre se obtiene del contexto autenticado o de un contexto explicito autorizado; nunca de un formulario clinico.

Regla obligatoria para modulos futuros:

> Todo dato propio de una clinica debe tener pertenencia verificable por empresa y trazabilidad del actor que lo registra o modifica.

## Contexto y trazabilidad

- `TenantContext` bloquea con 403 operaciones clinicas sin empresa.
- `BelongsToClinic` filtra modelos tenantizados, asigna empresa al crear y rechaza cambiarla en una edicion ordinaria.
- Se conservan creador, modificador y, cuando corresponde, anulador, fecha y motivo.
- `odo_auditoria` conserva cambios y anulaciones; los historicos sin evidencia mantienen esa limitacion.
- `TenantContext::run($clinicId, $actorId, ...)` establece y restaura contexto para comandos y trabajos.

## Evidencia reutilizable

Se incorporo el comando [VerifyMultiCompanyIsolation.php](../app/Console/Commands/VerifyMultiCompanyIsolation.php), ejecutable con:

```text
php artisan odontologia:verify-multi-company --clinic=201 --other-clinic=202 --json
```

Verifica tablas tenantizadas, conteos separados, columnas de trazabilidad y rechazo de una relacion paciente-cita cruzada. En la ejecucion MySQL aislada devolvio `passed: true` y `cross_company_relation_rejected: true`.

## Datos y excepciones

Son propios de una clinica: pacientes, odontologos, consultorios, citas, atenciones, historias, adjuntos, odontogramas, planes, presupuestos, procedimientos, sesiones, pagos, receptores, comprobantes, servicios, categorias, configuracion, correlativos y auditorias.

Son catalogos globales permitidos: roles, permisos y metodos de pago. Sus asignaciones y usos se validan dentro de la clinica.

## Archivos y respaldo

Los adjuntos clinicos se sirven desde almacenamiento privado por empresa mediante rutas autorizadas. Los cuatro adjuntos historicos fueron inventariados, verificados por tamano y SHA-256, migrados a privado y retirados de `storage/app/public`; su URL publica anterior respondio 404 y la ruta autorizada funciono.

El respaldo `storage/app/backups/before_multi_company_20261018_024450.sql` fue restaurado mediante PDO en una base temporal: 403 sentencias y 45 tablas restauradas.

## Migraciones ejecutadas

Se ejecutaron unicamente `000022_add_tenant_traceability`, `000023_add_attachment_integrity_metadata`, `000024_add_clinic_to_existing_audit` y `000025_remove_duplicate_clinic_foreign_keys`. No se uso `migrate:fresh` ni se reasignaron datos ambiguos.

## Matriz final

| Modulo | Estado | Operaciones aisladas | Trazabilidad | MySQL/automatizado | Navegador | Pendiente |
|---|---|---|---|---|---|---|
| Pacientes | Completo | Lectura, alta, edicion y URL cruzada rechazada | Creador/modificador | Aprobado | Alta y edicion en Clinica Norte; Clinica Sur no ve Norte | Anulacion manual |
| Citas y atenciones | Parcial | Modelos, relaciones y validaciones cruzadas | Actor y anulacion | Aprobado | Listado clinico aislado | Alta y anulacion manual cruzada |
| Historias y adjuntos | Parcial | Scope, descarga autorizada y URL publica retirada | Actor y auditoria | Hash, tamano, 404 publico y descarga privada aprobados | Descarga autorizada aprobada | Nueva copia historica |
| Odontogramas | Completo en rutas existentes | Filtrado y relaciones tenantizadas | Actor y auditoria | Aprobado | Pendiente recorrido manual cruzado | Exportadores futuros |
| Planes, presupuestos y sesiones | Completo en flujo existente | Relaciones y busquedas tenantizadas | Actor y auditoria | Aprobado | Pendiente recorrido manual completo | Politica de revisiones clinicas |
| Caja, pagos y constancias | Parcial | Filas, tickets, logos y correlativos separados | Actor y anulacion | Aprobado | Estado A4 y ticket 58/80 revisados; logo A4 servido con HTTP 200 | PDF binario y recorrido financiero cruzado ficticio |
| Servicios y tarifas | Completo en catalogo existente | Categorias, servicios y codigos por clinica | Actor y auditoria | Aprobado | Pendiente alta cruzada | Catalogos globales al extender |
| Configuracion | Completo | Configuracion independiente por clinica | Actor y auditoria | Aprobado | Pagina real revisada | Facturacion electronica |
| Usuarios y perfiles | Parcial | Usuario clinico limitado a su empresa; plataforma separada | Creador/modificador | Aprobado | Creacion de admin de clinica aprobada | Recorrido completo de permisos |
| Plataforma | Parcial | 403 a usuario clinico; empresas separadas | Actor de plataforma | Aprobado | Desactivar y activar con botones reales; empresa activa permitio login y operacion | Gestion avanzada de administradores |
| Reportes/exportaciones | Pendiente | No existe modulo funcional | No aplica | Sin pruebas | No aplica | Desarrollo futuro |
| Comandos/trabajos | Parcial | Contexto explicito y limpieza garantizada | Actor explicito | Comando de adjuntos y verificador aprobados | No aplica | Contexto en cada proceso futuro |

## Estado de cuenta A4 y verificacion navegable

La vista de estado de cuenta usa la configuracion de la clinica autenticada, muestra logo, datos de empresa, paciente, presupuesto, estado financiero, total, abonado, saldo, detalle e historial de pagos. El boton **Imprimir / Guardar PDF** usa el dialogo del navegador y oculta los controles al imprimir. Los tickets existentes de 58/80 mm no fueron reemplazados.

La causa del logo ausente era el uso de `asset('storage/...')` sin un enlace `public/storage` funcional. La correccion usa la ruta autenticada `odontologia.empresa.logo`, valida que el archivo pertenezca a la clinica activa y sirve el archivo desde el disco `public`. Las instantaneas historicas siguen usando su propia referencia; si no tienen logo, no se sustituye por el actual.

En MySQL temporal se usaron dos clinicas ficticias y usuarios separados. Se comprobo:

- Administrador de plataforma: desactivar y volver a activar Clinica Norte usando el boton real.
- Tras activar, el usuario de Clinica Norte pudo iniciar sesion y operar pacientes.
- Clinica Norte creo y edito un paciente; Clinica Sur intento abrirlo y recibio 404.
- Clinica Sur inicio sesion y su listado no mostro pacientes de Norte; tambien recibio 404 al consultar un paciente de Norte.
- La empresa inactiva bloquea una sesion clinica previamente abierta.
- La prueba automatizada de pagos conserva anulador, fecha y motivo. El estado A4 y el ticket de 58 mm se abrieron en navegador; la generacion de un archivo PDF binario no pudo ejecutarse porque el navegador integrado no expone `Page.printToPDF` y el entorno no tiene wkhtmltopdf/Chromium CLI. Queda pendiente solo esa comprobacion de archivo y el recorrido cruzado con pagos ficticios.

No se declara listo para produccion multiempresa completa hasta recorrer manualmente las operaciones financieras y de impresion con datos ficticios de ambas clinicas. Los modulos futuros y la facturacion electronica siguen fuera de alcance.

## Constancias de pago

La constancia usa el titulo `CONSTANCIA DE PAGO`, etiquetas de importe, metodo, fecha y receptor claramente diferenciadas y un unico pie: `Documento interno. No sustituye boleta ni factura`. Las fechas que solo tienen medianoche se imprimen sin hora.

Los pagos nuevos requieren nombre o razon social, RUC y direccion configurados antes de insertarse; la transaccion conserva la instantanea de empresa, logo, total, abonado y saldo. Los pagos historicos sin instantanea muestran un aviso fuera del area imprimible, no adoptan la configuracion actual y omiten el bloque financiero incompleto.

La prueba de pagos de [PlanesTratamientoTest.php](../tests/Feature/PlanesTratamientoTest.php) valida idempotencia, snapshot financiero, etiquetas del ticket y la referencia del logo para un pago nuevo. En navegador se verifico la constancia historica CI-000009 en 80 y 58 mm; por su falta de instantanea de empresa muestra el aviso correspondiente. No se registró un pago nuevo en la base real para probarlo manualmente.

## Usuarios, perfiles y permisos por clínica

Los roles existentes se tratan como plantillas globales. La migración `2026_10_18_000026_add_clinic_scope_to_roles.php` prepara `odo_roles.id_clinica`; cuando una clínica personaliza un perfil, se crea una copia local y se reasignan únicamente los usuarios de esa clínica, sin modificar la plantilla ni otras empresas. La administración de usuarios siempre toma `id_clinica` del contexto autenticado y descarta `es_admin_plataforma` recibido desde formularios.

El restablecimiento usa una ruta separada, valida contraseña nueva y confirmación, aplica `Hash::make()` y no registra la contraseña ni su hash. Las auditorías de cambios eliminan cualquier campo `password` antes de persistirse. La migración de roles debe ejecutarse antes de usar personalizaciones por clínica.
