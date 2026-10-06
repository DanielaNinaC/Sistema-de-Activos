# Bitácora técnica — SIATEC (Sistema de Activos Tecnológicos)

Registro de decisiones de diseño, invariantes, defectos corregidos y puntos
abiertos. Está pensado para responder "por qué está así" cuando el código no lo
explica por sí solo, y para no volver a tropezar con lo mismo.

Stack: Laravel 12 · PHP 8.2 · Tailwind CSS v4 (Vite) · MySQL (desarrollo) ·
SQLite en memoria (pruebas).

---

## 1. Invariantes del sistema

Reglas que hay que preservar. Romper cualquiera de ellas rompe la coherencia del
módulo de riesgos.

### 1.1 `MotorFmeaService` es la única fuente de verdad del cálculo

Ningún controlador ni vista debe reimplementar:

- **Umbrales de nivel de riesgo** (Tabla 29): `obtenerNivelYAlerta()`.
  `>= 8.00` Crítico · `>= 6.00` Alto · `>= 3.00` Medio · resto Bajo.
- **Score jerárquico de dos niveles**: `calcularScore()`.
  - Nivel 2 (riesgo técnico): `Subscore = S·w_sev + F·w_frec + MSR·w_msr`
  - Nivel 1 (macro): `Score = p1·Subscore + p2·Dependencia`
  - Con `p1 + p2 = 1` y ambos factores en `[1,10]`, el score queda garantizado en `[1,10]`.

**Ojo con la firma real** (fuente frecuente de malentendidos en las specs):

```php
public function calcularScore(int $S, int $F, int $MSR, int $D): float
```

Recibe **números y devuelve un float**. No recibe `(activo, modo_falla)` y **no
persiste nada**. Quien deriva S/F/MSR, persiste en `evaluacion_riesgo`, gestiona
`vigente`, alertas y tratamientos es `evaluarActivo()`.

### 1.2 Qué método hace qué

| Método | Responsabilidad |
|---|---|
| `MotorFmeaService::evaluarActivo($id, ?array $ids = null)` | Evalúa el catálogo de la categoría (o solo los IDs dados). Persiste, gestiona `vigente`, alertas y tratamientos. |
| `MotorFmeaService::evaluarTodos()` | Recorre el inventario llamando a `evaluarActivo()`. Devuelve un resumen del lote. |
| `ProcesoRiesgoController::evaluarActivo()` | Punto de entrada HTTP del motor automatizado. |
| `ProcesoRiesgoController::evaluacionStore()` | **Evaluación manual ad-hoc**: un solo par, con S/F/MSR escritos a mano. No es el motor. |

### 1.3 Historial real vía `vigente`

Cada par `(activo, modo_falla)` conserva su historial completo. Al reevaluar, la
fila anterior **de ese mismo par** pasa a `vigente = false` y se inserta una
nueva. Los demás pares no se tocan.

Consecuencia directa: **las evaluaciones parciales son válidas por diseño**. Si
se evalúan 2 de 5 modos, los otros 3 conservan su fila vigente. No están
"pendientes" ni "sin evaluar", y la interfaz debe decirlo así.

### 1.4 Vocabularios y parámetros: viven en catálogos de la BD (desde 2026-10-06)

Ya **no** hay constantes ni listas literales en PHP/Blade para estos valores. Cada
vocabulario es una tabla catálogo con su valor textual como **llave primaria
(clave natural)**, y la columna que lo usa tiene una **FK** hacia ella
(`ON UPDATE CASCADE`, `ON DELETE RESTRICT`). La columna sigue guardando el mismo
texto (`'Resuelto'`, `'Alto'`…), así que consultas, vistas y exportaciones no
cambian; lo nuevo es que MySQL rechaza valores fuera del catálogo.

El acceso es **solo** vía `App\Support\Catalogo` (cache por petición; hay que
llamar `Catalogo::flush()` si se editan catálogos dentro de un proceso largo).
Las validaciones usan `Rule::in(Catalogo::nombres('tabla'))`. Los catálogos se
siembran dentro de la migración `2026_10_06_100000_create_catalogos_de_parametros`
(no en seeders), porque las pruebas (SQLite) no corren seeders.

| Catálogo (tabla) | Contiene | Banderas / valores que reemplazan reglas del código |
|---|---|---|
| `niveles_dependencia` | Alta · Media · Baja | `valor` 9/5/2 (Tabla 8) |
| `niveles_severidad` · `niveles_msr` | claves `muy_alta`…`insignificante` / `muy_baja`…`muy_alta` | `valor` (Tablas 20 y 22) y `descripcion` |
| `niveles_frecuencia` | reglas de F evaluadas por `orden` | umbrales de desgaste e incidentes (Tabla 21) |
| `niveles_riesgo` · `tipos_alerta` | Bajo/Medio/Alto/Crítico ↔ Verde/Amarilla/Naranja/Roja | `puntaje_minimo` 0/3/6/8 (Tabla 29), `prioridad_sugerida`, `requiere_tratamiento` (Alto/Crítico), `notifica_alerta` (Crítico) |
| `tratamientos_sugeridos` | borrador automático por nivel | estrategia, acción y plazo (columna "Acción del Sistema") |
| `estrategias_tratamiento` · `estados_tratamiento` · `origenes_tratamiento` | Mitigar/Transferir/Evitar/Aceptar · Pendiente/En Proceso/Completado · automatico/manual | `genera_mantenimiento` (solo Mitigar), `es_abierto` |
| `estados_operativos` · `etapas_ciclo_vida` · `motivos_baja` | vocabulario del activo | — |
| `estados_incidente` · `prioridades_incidente` | Reportado…Cerrado · Alta/Media/Baja | `cierra_incidente` (Resuelto, Cerrado), `peso` de prioridad |
| `tipos_mantenimiento` · `estados_mantenimiento` | Preventivo/Correctivo/Programado/Inspección · Programado…Cancelado | `es_abierto`, `es_finalizado` (solo `Finalizado`) |

Consecuencias que hay que recordar:

- Agregar un valor = insertar una fila en el catálogo; ya no se toca código.
- `'Completado'` **no** es un estado de mantenimiento válido (la BD lo rechaza):
  es `Finalizado`.
- `modos_falla.nivel_severidad/nivel_msr` guardan la **clave** en snake_case
  (`muy_alta`); la etiqueta visible sale del catálogo.
- Varias FKs hacia padres que arrastraban datos principales pasaron de `CASCADE`
  a `RESTRICT` (rol→usuarios, categoría→activos, usuario→logs…): hay que
  reasignar antes de borrar.
- `categorias.nombre_categoria` y `(ubicaciones.seccion, ubicaciones.aula)` son
  únicos también a nivel de BD.
- **Pendiente de normalizar** (sigue en código): módulos y permisos por rol
  (`Usuario::permisosDefaultPorRol`, las 7 columnas `permiso_*`), los
  vocabularios del módulo de auditoría (`AuditoriaInterna::ESTADOS`,
  `HallazgoAuditoria::CLASIFICACIONES`…) y la detección del tipo de detalle de
  un activo por texto del nombre de la categoría.

**Valores reales persistidos que suelen confundirse:**

- `estado_mantenimiento` → `Programado` · `Pendiente` · `En ejecución` · **`Finalizado`** · `Cancelado`.
  Es **`Finalizado`**, no "Completado".
- `tipo_mantenimiento` → `Preventivo` · `Correctivo` · `Programado` · `Inspección` (4 valores, no 2).
- `criticidad_institucional` del activo tiene **4** niveles (`Crítica`/`Alta`/`Media`/`Baja`)
  frente a **3** de prioridad, por eso `Crítica` se absorbe en `Alta`.

### 1.5 Cierre del ciclo ISO 31000

```
Evaluación (Alto/Crítico)
   └─> Tratamiento sugerido automáticamente (borrador Pendiente)
         └─> Si la estrategia es "Mitigar": Mantenimiento programado
               └─> Al FINALIZAR el mantenimiento:
                     ├─ el tratamiento pasa a Completado
                     └─ se dispara una nueva evaluación del activo
```

Decisiones que sostienen este flujo:

- **Solo `Mitigar` genera mantenimiento.** `Transferir`, `Evitar` y `Aceptar` no
  implican intervención física sobre el activo.
- **El disparador vive en el mantenimiento, no en el tratamiento.** El hecho
  físico que cambia el score es la ejecución real del mantenimiento. Marcar un
  tratamiento como Completado a mano **no** dispara reevaluación.
- **El vínculo reutiliza `mantenimientos.id_evaluacion_origen`**, que ya existía.
  No se creó ninguna columna para esto.
- **El desgaste se reinicia con el mantenimiento.** `calcularFechaBaseDesgaste()`
  usa la fecha más reciente entre adquisición y último mantenimiento
  `Finalizado`, y el conteo de incidentes para F se acota a los posteriores.
- **Idempotencia**: no se duplican alertas si el nivel no cambió, ni tratamientos
  o mantenimientos si ya hay uno activo para el mismo par.

---

## 2. Trampas del entorno (ya nos costaron tiempo)

| Trampa | Qué pasa | Cómo evitarla |
|---|---|---|
| **Tailwind v4 + clases interpoladas** | `bg-{{ $color }}-50` nunca se genera: el escáner no detecta nombres construidos. | Escribir siempre la clase completa y estática. |
| **Especificidad de ID sobre componentes** | Una regla `#main-content form button` le gana a `.btn`. | No usar selectores con ID para estilar componentes. |
| **Carbon 3** | `diffInDays()` devuelve **float**, no int. | Castear a `(int)`. |
| **`range(1, 0)` en PHP** | Devuelve `[1, 0]`, no lista vacía. | Guardar con `$n > 0 ? range(1,$n) : []`. |
| **Checkbox desmarcado** | No se envía; el backend cae en el valor por defecto. | Input oculto con el valor alternativo justo antes del checkbox. |
| **Defaults de BD y modelos recién creados** | Tras `Model::create()` la instancia **no** trae las columnas con `DEFAULT`. En pruebas, `CheckModuleAccess` los leía como `null` → 403. | `$model->refresh()` antes de `actingAs()`. |
| **Navegación SPA (`turbo-nav`)** | El JS de página debe re-inicializarse en cada navegación. | Patrón `readyState` + `document.addEventListener('turbo:load', init)`, y guard en el elemento (`dataset.*`), no con `removeEventListener`. |

### Verificar antes de asumir

Varios errores reales de esta bitácora venían de confiar en documentación en vez
del código. Conviene comprobar siempre:

- La **firma real** de los métodos que se van a usar.
- El **tipo de retorno real** de librerías externas. Ejemplos encontrados:
  `Pdf::download()` devuelve `Illuminate\Http\Response` (no `BinaryFileResponse`)
  y `Excel::download()` devuelve `BinaryFileResponse` (no `StreamedResponse`).
  Ambos causaron HTTP 500.
- Los **valores realmente persistidos** en la base, no los que sugiere el nombre.

---

## 3. Defectos encontrados y corregidos

### 3.1 Preexistentes (no introducidos en el desarrollo reciente)

- **`modos_falla` con `valor_severidad_base` y `valor_msr_base` en `NULL`.**
  Las columnas se añadieron en una migración posterior y `insertOrIgnore` nunca
  actualiza filas existentes. El motor llevaba tiempo usando el fallback
  genérico `S=5 / MSR=4` para **todas** las evaluaciones reales. Corregido en la
  base y en el seeder.

- **Indicador de estado del botón "Evaluación Manual" que no funcionaba.**
  El JS añadía `bg-slate-700` a un botón con `bg-slate-900`; Tailwind ordena las
  utilidades por tono, así que slate-900 siempre ganaba. Quedó arreglado al
  migrar la base a `.btn-dark` (capa components, que pierde frente a utilities).

- **Migración redundante y rota en SQLite** (`make_apellido_nullable`), con
  sintaxis `ALTER TABLE ... MODIFY` exclusiva de MySQL. Eliminada.

- **Vocabulario no canónico en datos sembrados**: `'Prevenir'`, `'En ejecución'`
  como estado de tratamiento, `'Realizado'` como estado de mantenimiento.
  Corregido en el seeder y en las filas ya existentes.

- **Icono "Editar" con `d` malformado** (`...0 002 h11...`, falta un número) en
  **10 vistas**. Genera errores de consola. **Sin corregir** — ver §5.

### 3.2 Introducidos y corregidos en el desarrollo reciente

- **`remember_token` inexistente.** Al añadir la casilla "Recordarme",
  `Auth::attempt(..., true)` intentaba escribir una columna que no existía.
  Se añadió la migración **y** el middleware `EnsureUserIsActive`, porque la
  cookie de sesión persistente reautentica **sin pasar por `login()`**, saltándose
  el control de `estado`.

- **Filtro de modos de falla que dependía de haber elegido activo.** La función
  retornaba antes del bucle cuando no había activo seleccionado, dejando las
  etiquetas en su estado inicial del HTML: **todas las categorías visibles**.
  Cualquier camino que revelara el bloque sin una pasada previa del filtro
  exponía el catálogo completo. El estado por defecto correcto es "ningún modo
  aplicable", no "todos".

- **JS de página sin `turbo:load`.** Al volver a la pantalla por el menú lateral,
  el `<select>` quedaba sin listener y cambiar de activo no refrescaba nada.

---

## 4. Módulos y decisiones destacadas

### 4.1 Sistema de botones (`resources/css/app.css`)

Fuente única de verdad del estilo. Antes convivían ~12 variantes del mismo botón
primario (padding de `px-2.5` a `px-6`, `text-xs`/`text-sm`, sombras distintas).
Hoy: **182 botones** normalizados en 11 variantes.

- Base `.btn` + tamaños `.btn-sm` / `.btn-xs`
- Variantes por intención: `primary` · `secondary` · `success` · `warning` ·
  `danger` · `accent` (exportar PDF) · `dark` · `outline` · `ghost`
- Formas: `.btn-icon`, `.btn-icon-sm`, `.btn-tile` (botón grande tipo panel)

**Decisión de color**: el azul `bg-blue-600` se mantiene como color de acción; el
azul marino `#1D3557` queda como color de interfaz (barra lateral, cabeceras,
login). El contraste entre ambos es intencional.

Si un botón necesita otro aspecto, se agrega una variante aquí — no se vuelve a
escribir una cadena de utilidades en el Blade.

### 4.2 Priorización de incidentes

Campo `prioridad` (`Alta`/`Media`/`Baja`, default `Media`). Es **manual con
sugerencia**: se pre-selecciona a partir de `criticidad_institucional` del activo
(que mantiene el motor FMEA), pero quien reporta decide y puede cambiarla; una
vez elige a mano, deja de sugerirse.

Por qué no derivarla del FMEA automáticamente: 9 de 10 incidentes no tienen
`fk_id_modo_falla`, así que habrían quedado sin prioridad.

El seguimiento se ordena por urgencia con un `CASE` construido desde la constante
de pesos — no por la columna (daría *Alta, Baja, Media*, alfabético) ni con
`FIELD()`, que es exclusivo de MySQL y rompería las pruebas en SQLite.

### 4.3 Selección granular de modos de falla

`evaluarActivo($id, ?array $ids = null)`: con `null` evalúa todo (comportamiento
por defecto intacto); con un array filtra la colección **antes** del bucle. El
scoring no cambió — solo qué pares entran.

`EvaluarActivoRequest` valida el contrato `modo_evaluacion = todos|especificos` y
**rechaza cualquier ID que no pertenezca a la categoría del activo**.

En el servidor "Todos" nunca pudo incluir otras categorías: parte de
`$activo->categoria->modosFalla`. El fallo del §3.2 era solo de interfaz.

### 4.4 Historial de incidentes por activo

Una fila por activo con incidentes, con `withCount` y `withMax` sobre la relación
(los totales y la última fecha salen en la misma consulta, sin traer los
incidentes a memoria). El detalle lista todos los incidentes de ese activo.

### 4.5 Informes

- Los PDF (dompdf) **no pueden ejecutar JavaScript**: las vistas de PDF son HTML
  y tablas. Chart.js solo en pantalla.
- `EvaluacionRiesgo::scopeSinTratamiento()` es la fuente única compartida entre
  el módulo de Tratamiento y el de Informes.
- Se eliminó el "Tablero de Control" por duplicar el Dashboard.

### 4.6 Validaciones de formularios (2026-10-06)

Mensajes en español: `APP_LOCALE=es` + `lang/es/validation.php` (con los nombres
amigables de cada campo en `attributes`). También pasa a español `diffForHumans()`
y `translatedFormat()`. Los vocabularios se validan contra los catálogos
(`Rule::in(Catalogo::nombres(...))`), ver §1.4.

Reglas de coherencia agregadas (pruebas en `ValidacionesFormulariosTest`):

- **Evaluación manual de riesgo:** el modo de falla debe ser de la categoría del
  activo (igual que el motor automático) y la fecha no puede ser futura.
  `ActivoTecnologicoController::show()` ya no ofrece modos de otras categorías.
- **Incidente:** fecha no futura y no anterior a la adquisición del activo.
- **Mantenimiento:** `fecha_ejecucion >= fecha_programada`; el incidente y la
  evaluación vinculados deben ser del mismo activo.
- **Activo:** `fecha_adquisicion` no futura; `direccion_ip` es una IP válida;
  RAM, discos y calidad de imagen `>= 0`.
- **Usuarios:** no se puede desactivar la propia cuenta ni al último
  administrador activo.
- **`ActivoTecnologicoController::store()/update()`** validan activo + ficha
  técnica **antes** de guardar y escriben en una transacción (antes el activo se
  creaba y luego fallaba el detalle, dejándolo a medias). Si la categoría no tiene
  ficha técnica específica y se llenaron datos técnicos, el mensaje de éxito avisa
  que no se guardaron.
- Pendiente (no se impuso, depende de convención institucional): longitud mínima
  de `descripcion_falla`, unicidad de `nombre_modo_falla` por categoría,
  confirmación de contraseña al crear usuarios desde el panel.

**Errores junto a cada campo (todos los formularios).** Un solo mecanismo en vez
de `@error` en cada input: el layout deja los errores de la bolsa `default` en
`<div id="errores-validacion" data-errores="…">` (dentro de `#main-content`) y
`resources/js/errores-campos.js` los coloca bajo cada campo, lo resalta con
`aria-invalid="true"` (regla en `app.css`), reabre el modal si el campo está en
uno (solo si su `<form>` tiene `action`) y muestra un aviso arriba únicamente
cuando no encuentra el campo. Un campo oculto se asocia a uno visible con
`data-error-de="nombre_del_campo_oculto"` (ver `incidentes/registrar`).

Trampas que salieron al hacerlo:

- **`turbo-nav` recrea los `<script>` copiando solo su texto** (se pierden `id` y
  `type`): los datos no pueden viajar en un `<script type="application/json">`.
- **Los envíos de formularios por `turbo-nav` no disparan `turbo:load`** (solo la
  navegación por enlaces). Por eso `errores-campos.js` observa `#main-content`
  con un `MutationObserver` en vez de depender de ese evento.
- **El resaltado de borde no se puede hacer con clases de Tailwind**: la regla
  global `#main-content input {border-color}` tiene especificidad de ID y gana.
- **El modal "Editar Mi Perfil" (layout) se abría con CUALQUIER error de
  validación**, porque el perfil usaba la bolsa por defecto. Ahora
  `PerfilController` usa `validateWithBag('perfil', …)` y el layout lee
  `$errors->perfil`.
- Para verificar estilos en el panel del navegador: si `document.visibilityState`
  es `hidden` las transiciones CSS no avanzan y `getComputedStyle` devuelve el
  valor anterior (anular `transition` para medir).

---

## 5. Puntos abiertos (requieren decisión)

1. **Test obsoleto.** `ExampleTest::test_the_application_redirects_guests_to_login`
   espera que `/` redirija al login, pero `/` ahora sirve una landing pública
   (`InicioController`). Es el **único fallo** de la suite.

2. **Permisos de "Configuración de Evaluación".** La especificación pedía
   restringirla a Administrador "heredando la restricción de Score de Riesgo",
   pero hoy Score **no** es exclusivo de Administrador: se rige por
   `modulo:riesgos`, y `permiso_riesgos` está en `true` también para **Soporte**.
   Se preservaron los permisos tal cual estaban (criterio de aceptación
   explícito). Si la intención real es restringir a Administrador, es un cambio
   de control de acceso pendiente.

3. **Endpoint huérfano de alertas.** Se retiró el alta manual de alertas de la
   interfaz, pero `POST /admin/riesgos/alertas` y `alertasStore()` siguen
   existiendo. Mientras estén, un administrador podría crear alertas por fuera
   del motor, saltándose la idempotencia.

4. **Icono "Editar" malformado** en 10 vistas (§3.1). Arreglo de un carácter.

5. **Deriva de esquema**: la migración declara `usuarios.apellido` como
   `nullable()`, pero en la base MySQL está como `NOT NULL`. Recrear la base
   desde cero daría un comportamiento distinto al actual.

6. **`resources/views/auth/borrador.blade.php`** es código muerto: ninguna ruta
   ni controlador lo referencia.

7. **`admin/usuarios/edit.blade.php`** conserva inputs sin estilo
   (`border px-3 py-2 rounded`), descolgados del resto del sistema.

---

## 6. Estado de las pruebas

**53 pasan · 1 falla** (la del punto 1 de §5) · 192 aserciones.

| Suite | Cubre |
|---|---|
| `Unit\MotorFmeaServiceTest` | Umbrales, antigüedad, score, alertas idempotentes, `vigente`, tratamiento automático, reinicio de desgaste, selección granular |
| `Feature\EvaluacionGranularTest` | Contrato `todos`/`especificos`, rechazo de modos de otra categoría, categoría sin modos |
| `Feature\PlanTratamientoTest` | Cierre del ciclo Tratamiento ↔ Mantenimiento |
| `Feature\PrioridadIncidenteTest` | Orden por prioridad, vocabulario, conservación al actualizar |
| `Feature\InformesTest` | Vida útil, modos frecuentes, sin tratamiento, exportación |
| `Feature\AutenticacionTest` | Login, "Recordarme", expulsión de cuenta desactivada |

Las pruebas usan **SQLite en memoria**; el desarrollo usa **MySQL**. De ahí la
regla de no usar SQL exclusivo de un motor.

### Captcha en pruebas

El código se indexa en cache por `session()->getId()` y cada petición de prueba
abre una sesión nueva, así que **no se puede sembrar**. En las pruebas de
autenticación se sustituye la fachada `Cache`; es un detalle ortogonal a lo que
esas pruebas verifican.

---

## 7. Entorno de desarrollo

```bash
php artisan serve      # http://127.0.0.1:8000
npm run dev            # Vite en :5173 (necesario: sin él la app se queda sin CSS)
```

- `.claude/launch.json` tiene ambos servidores configurados.
- Si existe `public/hot` pero Vite **no** está corriendo, `@vite()` apunta a un
  servidor muerto y la aplicación se queda **sin estilos**.
- La base de datos de desarrollo corre en **XAMPP** (`C:\xampp`); si MySQL está
  detenido, la app devuelve `SQLSTATE[HY000] [2002]`.
- La extensión `gd` de PHP tuvo que habilitarse en `C:\php82\php.ini` para
  `phpoffice/phpspreadsheet` (cambio a nivel de sistema, no del proyecto).
