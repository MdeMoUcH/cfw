# Desarrollar sobre CFW
Reglas y trampas para construir o cambiar un sitio hecho con CFW. Para saber qué es y cómo enruta, ver [README.md](README.md); para instalarlo y configurarlo, [INSTALACION.md](INSTALACION.md); para actualizar un sitio, [MIGRACION.md](MIGRACION.md). Si este documento y el código no coinciden, manda el código.

## Dónde va cada cosa
- Un sitio no cambia `cfw/general/`, `cfw/includes.php`, `cfw/index.php` ni `index.php`: son de CFW. Al actualizar, `git merge` fusiona los cambios de CFW con los del sitio, y cada cambio del sitio en esos ficheros acaba en un conflicto (ver «Actualizar un sitio» en [INSTALACION.md](INSTALACION.md)).
- Lo propio va en `cfw/specific/`, que CFW trae solo con los `index.php` de protección: lo que el sitio cree ahí no choca con CFW al actualizar. La configuración no va aquí, sino en `config/` (ver «Configuración»).
- El sitio adapta también ficheros fuera de `specific`, que sí pueden dar conflictos: `config/config.ini`, el `.htaccess` raíz, `db.sql`, `css/`, `js/` e `img/` (ver «Qué queda fuera de `specific`» en [README.md](README.md)).
- Cada carpeta de `specific` actúa de una forma (ver «Qué se puede redefinir en `specific`» en [README.md](README.md)):
  - `pages/` y `templates/`: un fichero con la misma ruta que uno de `general` lo sustituye entero; uno nuevo se añade.
  - `class/` y `orm/`: solo los de la lista fija de `cfw/includes.php` (las clases de `cfw/general/class/` y los ORM `Usuarios` y `DinamicOrm`). El fichero del sitio sustituye al de `general`, y su clase hereda de la base de `general` para redefinir solo lo que cambia (ver el punto siguiente). Una clase o un ORM nuevos no se cargan solos: la página que los use hace `require_once` de su ruta.
  - `lang/<código>/`: no se copia nada. Los textos se fusionan clave a clave con los de `general` (ver «Textos»), y basta con poner las claves que cambian.
  - `utils/functions.php`: se carga además del de `general`, no lo sustituye. Definir en él una función que ya existe en `general` da un error fatal.
- **Redefinir una clase de `class/` u `orm/`.** Cada clase de la lista tiene en `general` una base con todo el código y una clase pública vacía que hereda de ella. El sitio declara en `cfw/specific/class/<Clase>.php` (u `orm/<Orm>.php`) la clase pública heredando de la base, y redefine solo lo que cambia:
  - `class AppConf extends AppConfCFW`, y así `App`, `Msession`, `Cifrado`, `GestorIdiomas`, `Bbdd`, `Template`, `Page`, `Orm`, `Form`, `AdminHelper`, `Usuarios` y `DinamicOrm` con `<Clase>CFW`.
  - `Error.php` declara `class ErrorCFW extends ErrorBaseCFW`: `ErrorCFW` ya es el nombre público, porque `Error` es una clase de PHP.
  - `PaginaAdmin` es una interfaz vacía sin base: solo se puede copiar, y debe seguir vacía. Si se le añaden métodos, `users`, `idiomas` y `AdminHelper` dan un error fatal.
  - Las bases se cargan siempre de `general` y no se sustituyen: un `specific/class/<Clase>CFW.php` se ignora. Sus nombres están reservados (ver [MIGRACION.md](MIGRACION.md)).
  - Una copia entera de la clase en `specific` también carga, como antes, pero deja de recibir las correcciones de CFW: anota en el sitio qué ficheros están copiados y por qué, y revísalos en cada actualización (paso 5 de «Actualizar un sitio» en [INSTALACION.md](INSTALACION.md)).
- **Clases intermedias.** Cada base hereda de la clase pública de su padre, no de su base:
  - `AppConf` → `App` → `Bbdd` → `Orm` → `Usuarios`, `DinamicOrm` y los ORM del sitio.
  - `App` → `Template`.
  - `App` → `Page` → `ErrorCFW`, `AdminHelper` y las páginas.
  - `AppConf` → `Msession`.
  - `Cifrado`, `GestorIdiomas` y `Form` no tienen padre.

  Por eso lo que redefine el sitio en `AppConf`, `App`, `Bbdd`, `Page` u `Orm` lo heredan todas las clases y páginas de `general` que cuelgan de ella. Su constructor no debe crear objetos que hereden de ella (`Template`, `Usuarios`, una página…): entra en una recursión infinita. El constructor de `App` se ejecuta además en cada `Template`.
- **Cuándo llamar a `parent::`.** Siempre en `__construct`; en `Template`, pasando `$template_file`. Y, al redefinirlos:
  - `Msession::setUserLogged` y `setUserOut`.
  - `Page::show`.
  - `Orm::filtro`, `filtroId`, `update` e `insert`.
  - `Usuarios::comprobarUserPass`, `insert` y `update`.
  - `Form::getForm`: sin `parent::getForm()` pierde el escapado y los ids. `setMethod` y `addContentTable`: sin `parent::`, la segunda pintada no regenera el `csrf` o el contenido, y sus ids se repiten (ver «Ids de `Form`»).
- **Avisos de cada clase:**
  - `AppConf`: `config` se cambia después de `parent::__construct()`. El cambio afecta también al `new AppConf()` de `index.php`, `Cifrado`, `GestorIdiomas` y `functions.php`.
  - `Page`: `exigirUsuario`, `exigirAdmin` y `adminRevalidado` son `final`, y la revalidación es `private`: no se pueden redefinir.
  - `AdminHelper`: sin `parent::__construct()` no hay `exigirAdmin` y la página da 403. Un método público nuevo del `AdminHelper` del sitio se puede llamar por URL en todas las páginas que heredan de él: lo que no deba serlo va como `protected` o `private`.
  - `Usuarios` y `Msession`: los usan el login, la sesión y la revalidación de `Page`. Redefinirlos sin `parent::` debilita el login.
  - `Bbdd`: las constantes `TIEMPO_*` se leen con `self::`, así que redefinirlas no cambia nada.
  - `Cifrado`, `GestorIdiomas` y `Form`: sus llamadas internas usan `self::`. Redefinir un método estático solo cambia las llamadas desde fuera de la clase: `GestorIdiomas::activos()` sigue usando el `porDefecto()` de la base.
  - Los métodos `private` de una base no se pueden llamar ni redefinir desde la clase del sitio.
  - Los errores y las trazas del log pueden nombrar la base (`PageCFW::show()`) en lugar de la clase pública.
- Antes de copiar, mira si basta con una página nueva que herede de `Page` o de `AdminHelper`, unas claves en `specific/lang/` o una plantilla nueva.
- Una mejora que sirva a cualquier sitio no se hace en el sitio: se propone para CFW.

## Páginas
- Fichero `cfw/specific/pages/<nombre>.php`, con una clase del mismo nombre, en minúsculas, números y `_`, que herede de `Page`, directamente o a través de otra como `AdminHelper`. El constructor llama a `parent::__construct()`.
- `init()` atiende la URL sin método. Con método, `/admin/<nombre>/<método>/<arg1>/<arg2>` ejecuta `$page-><método>($arg1, $arg2)`: dos argumentos como máximo; con más, o con `//` en la ruta, da 404. **Los que faltan llegan como `null`**, también si la URL acaba en barra, y como `""` solo con `index.php?...&tres=`, no con el valor por defecto del parámetro: compruébalos con `== ""`, no con `=== null`. `empty()` también es cierto con `"0"`: con `/admin/<nombre>/ver/0`, toma el `0` por un argumento que falta.
- Es accesible por URL cualquier método público de la página o de una clase intermedia (por ejemplo, `pag`, `ver` o `form` de `AdminHelper`). No lo son los declarados en `AppConf`, `App` o `Page` (ni en sus bases `AppConfCFW`, `AppCFW` y `PageCFW`), los que se llaman como un método de `Page`, los estáticos, los abstractos ni los que empiezan por `__`. Lo que no deba llamarse por URL va como `protected` o `private`.
- `/admin/<nombre>` es solo una forma de la URL: **no protege nada**. Protege el constructor:
  - `$this->exigirUsuario()` para páginas con sesión.
  - `$this->exigirAdmin()` para administración. Declara además `implements PaginaAdmin`: así `index.php` responde 403 si el constructor no lo ha llamado.
  - Los dos comprueban en la BD que el usuario sigue activo (y administrador): si no, cierran la sesión y llevan al login, y si la BD falla o no responde a tiempo responden 503.
  - A una petición con `X-Requested-With: XMLHttpRequest` (sin distinguir mayúsculas) no la llevan al login: responden 401 `text/plain` con `text_sesion_caducada`, sin pasar por `redirigir`. Prototype la envía siempre, y jQuery solo en las peticiones al mismo origen (sin `crossDomain`); un `fetch` propio debe enviarla para recibir el 401 en lugar del login.
  - Las subclases de `AdminHelper` ya lo hacen con `parent::__construct()`.
  - `exigirUsuario()` solo garantiza que hay sesión. Si la página recibe el id de un recurso, comprueba que es de ese usuario (`$this->session->data["id_usuario"]`), o cualquiera con sesión lee o cambia los de otro.
- **El constructor se ejecuta antes de que `index.php` compruebe el token CSRF y el administrador.** No proceses POST en el constructor, y en una página de administración no hagas nada antes de `exigirAdmin()` (o de `parent::__construct()` en una subclase de `AdminHelper`).
- Una URL pública sin `/admin/`, como `/contacto`, necesita su regla en el `.htaccess` raíz, anclada (`RewriteRule ^contacto/?$ /index.php?uno=contacto`): sin `$` también coincide con `/contactos`. O usar `index.php?uno=contacto`.
- Todo POST exige un campo `csrf` válido, o responde 403 sin ejecutar el método. `Form` lo añade con `setMethod("POST")`, con un id único (ver «Ids de `Form`»); en una plantilla propia, `<input type="hidden" name="csrf" value="{csrf}" />`. Las demás peticiones no pasan por esa comprobación: lo que cambie datos va por POST y el método lo comprueba (`$_SERVER["REQUEST_METHOD"] === "POST"`), como `AdminHelper::delete`.
- Para pintar: `$tpl = new Template("ruta/plantilla")`, rellena `$tpl->vars` y termina con `$this->show($tpl)`, que mete la plantilla en `cascara.tpl` y corta la ejecución. Las variables de la plantilla pasan también a `cascara.tpl` y pisan las de la página con el mismo nombre. El menú se pone con `$this->vars["menu"] = $this->get_menu("<activo>")` y el título con `$this->vars["page_title"]`; sin título sale `name` de la configuración.
- `get_menu` rellena `{enlace_sesion}` de `menu.tpl`: sin sesión, el enlace «Login»; con sesión, el botón «Salir», un POST a `login/logout` con `csrf` (plantilla `html/form_salir`). Un `menu.tpl` propio debe usar esa variable. `{boton_salir}` sigue disponible en las plantillas propias, con el mismo botón cuando hay sesión, pero las de CFW ya no lo pintan: con el del menú basta.
- CSS y JS de una página: `$this->add_include("css"|"js", $url)`. La URL se pinta sin escapar: solo URL propias.
- Una página propia que cargue `js/tablekit.js` carga después `js/tablekit-cfw.js`, como hace el listado de `AdminHelper`. Corrige el orden de estos tipos:
  - `currency`: con el símbolo (`$ £ ¥ € ¤`) delante o detrás, y sin mirar el idioma. Con `.` y `,`, el último es el decimal; con uno solo, es de miles si se repite o le siguen exactamente 3 dígitos, y si no, decimal. Un importe con 3 decimales se lee, por tanto, como miles.
  - `number`: el primer número del texto. Con los dos separadores, el último es el decimal; con uno solo, decide el `lang` de `<html>`: en español, `1.5` vale 15.
  - `TableKit.getCellText` devuelve el texto sin espacios a los lados, para la detección, el orden y el editor en línea.
- Un error que corta: `$this->showError($texto)`, que escapa el texto y no cambia el código HTTP: pon antes `http_response_code()` si no es un 200.
- Un mensaje tras redirigir: `guardarFlash($color, $clave)`, con `Red`, `Green` o `Blue` y la clave de `textos.ini`, no el texto, y `leerFlash()` en la página de destino, que devuelve el mensaje ya pintado.
- `redirigir($url)` corta la petición. Úsalo solo con URL propias, construidas desde `$this->config["urlbase"]`. `exigirUsuario()` y `exigirAdmin()` no lo llaman cuando responden 401 a un XHR.
- Un listado propio se pagina con `datosPaginacion($orm, $pag, $filas)`, que devuelve `pagina` (ya acotada), `paginas` y `por_pagina`, `$orm->getAllPaginado($pagina, $por_pagina)` y `enlacesPaginacion($url, $pagina, $paginas)`, que devuelve los enlaces a `$url.<n>` ya pintados, o `""` con una sola página. `datosPaginacion` y `enlacesPaginacion` son métodos `protected` de `Page`, y `getAllPaginado`, de `Orm`; los usa también el listado de `AdminHelper`. `getAllPaginado` no acota a la última página: una página no válida da la primera, y una posterior a la última, aunque sea enorme, da `false` sin error.

## Ids de `Form`
- Todo id que genera `Form` (el formulario, el `csrf`, los campos, las opciones de `radio` y `checkbox` y los grupos) es único en la petición: si ya está usado, lleva `_2`, `_3`…, y su `label for` y su `aria-labelledby` se ajustan. El `name` no cambia.
- **Pintar dos veces**: cada `getForm()` da ids nuevos (formulario, `csrf`, campos, opciones y grupos), con `_2`, `_3`…, y el mismo `name`. Si el contenido se asigna a mano en `vars["content"]`, sus ids se reservaron al generarlo: para pintarlo dos veces, genéralo dos veces.
- Tampoco coincide nunca con un id literal de las plantillas de `cfw/general/templates` y `cfw/specific/templates`, aunque esa plantilla no se pinte en la página. Esos ids no cambian: cede `Form`.
- **Ids solo de dígitos**: `Form` no genera nunca uno: el nombre `5` sale como `5_2`, porque las filas del listado usan el id de la BD.
- **Ids con variables** (`id="{…}"`) que no sean el número de una fila: no cuentan; resérvalos con `Form::reservarId`.
- **`Form::reservarId($id)`**: para los ids que la página pinta en PHP. Llámalo antes de generar los formularios: no renombra lo ya generado, y devuelve `false` si el id ya estaba usado (o está vacío o es solo de dígitos).
- **`Form::idUnico($base)`**: da un id libre para un elemento propio o para una plantilla que se pinta más de una vez, con `id="{id}"`. El valor se escapa con `escapar()` al asignarlo.
- **Ids fijos repetidos entre plantillas o dentro de una misma**: `Form` no los renombra. Una plantilla con ids fijos se pinta una vez por página; si se repite, sus ids van en variables con `Form::idUnico`.
- **Ids creados por JS**: `Form` no los puede conocer. Los de TableKit (`<id de la tabla>-cell-<n>`, su `-form` y `tablekit-table-<n>`) solo coinciden con un `name` de esa forma. Un JS propio debe usar ids que no sean `name` de ningún campo.
- Por eso el botón por defecto (`name="button"`) suele salir como `button_2`, y un campo `email` o `id` como `email_2` o `id_2`.
- Para usar un campo desde JS, búscalo por `name` dentro de su formulario (`form.querySelector('[name="email"]')`), o elige nombres que no estén en las plantillas.
- El `name` del formulario (`$form->vars["name"]` o `setName`) se escapa al pintarlo: asígnalo sin escapar. Sin `name`, el formulario lleva `id="form"`.

## Plantillas
- Un `.tpl` es HTML con `{nombre}` (letras, números, `-` y `_`). No hay condiciones ni bucles: una lista se monta en PHP, con una plantilla por elemento, y se asigna ya compuesta.
- **Los valores no se escapan al sustituir.** Todo dato que venga del usuario o de la BD se escapa antes de asignarlo, según dónde se pinte:
  - Texto y atributos entre comillas, dobles o simples (`<div class="{x}">`): `escapar()`. Un atributo sin comillas no queda protegido.
  - URL en `href`, `src` o `action`: `escapar()` no impide `javascript:`. Una URL que venga de fuera se pinta solo si empieza por `http://`, `https://` o `urlbase`, y además pasa por `escapar()`.
  - Atributos de evento (`onclick`...) y `style`: no se meten datos; el navegador decodifica las entidades antes de ejecutarlos.
  - Dentro de un `<script>`: `json_js()`, que devuelve el valor con sus comillas, así que va sin comillas alrededor (`var x = {x};`, como `html/js_var.tpl`).
- La sustitución es de una sola pasada: un valor con `{otra}` no se expande.
- De dónde sale cada valor, de más a menos prioridad:
  - Lo asignado en `$tpl->vars`.
  - El `.ini` con el nombre y la ruta de la plantilla en `lang/<código>/`, como `portada.ini` para `portada.tpl`. Se lee al crear el `Template`, así que lo que asignes después lo pisa.
  - `{csrf}`, con el token de la sesión.
  - `{text_...}`, de `textos.ini`.
- Lo demás queda vacío, salvo un `text_` que falte, que sale con su nombre y deja una línea `CFW: falta el texto de idioma` en el log.
- Los nombres de plantilla solo admiten letras, números, `_`, `-` y `/`. Una plantilla que no existe responde 404.

## Textos
- Ningún texto visible se escribe en PHP ni en la plantilla. Va en `textos.ini`, con clave `text_...`, y se usa con `{text_...}` o con `t("text_...", array("x" => $valor))`, que cambia `{x}` por el valor. Los de una sola plantilla pueden ir en su `.ini` (ver «Plantillas»).
- El sitio crea sus ficheros en `cfw/specific/lang/<código>/`, carpetas que CFW trae con solo su `index.php`: son ficheros nuevos y no dan conflicto al actualizar. Se fusionan clave a clave: `general` y `specific` de `lang_default`, y encima los del idioma actual. Basta con poner las claves que cambian o se añaden.
- Los textos se pintan sin escapar, y los parámetros de `t()` deben llegar ya escapados.
- Los `.ini` de idioma se leen con `INI_SCANNER_RAW`, y sus reglas no son las de `config.ini`:
  - Escribe los valores entre comillas dobles: sin comillas, un `;` corta el valor sin error.
  - Ninguna `"` dentro del valor, tampoco escrita como `\"`.
  - `\` y `${` salen tal cual, sin escaparlos.
  - Las claves solo admiten letras, números y `_`.
  - Un fichero que no cumple, o con una comilla sin cerrar, se ignora entero y deja `CFW: fichero de idioma no válido: <ruta>` en el log.
- Una clave nueva se añade en todos los idiomas del sitio. La que falte en uno sale en `lang_default`.

## Datos
- Un modelo es una clase en `cfw/specific/orm/` que hereda de `Orm` y, en su constructor, después de `parent::__construct()`, define `tabla`, `campos` y, si se va a usar con `AdminHelper`, `campos_form`. `includes.php` no la carga: la página que la use hace `require_once` de su ruta.
- `campos` es un array `columna => tipo`, del que solo cuentan las claves: es la lista blanca de columnas que escriben `insert` y `update`, que descartan las demás. Aun así, no les pases `$_POST` entero si la tabla tiene columnas que el formulario no debe tocar, como un tipo de usuario: pasa un array con lo que toca, como `cuenta` con el email.
- Columnas que el `Orm` da por hechas:
  - `id`, numérico y `AUTO_INCREMENT`: casi todos los métodos filtran por la columna `id`, también `update` y `delete`. `primarykey` solo la usan `eliminarFila` y `updateField`, y `update` para no escribirla. Usa `id` como clave primaria. Sin `AUTO_INCREMENT`, un alta de `AdminHelper` crea la fila, pero no guarda sus subidas ni sus categorías: avisa en rojo y deja una línea `CFW:` en el log. Nunca usa el id de la URL.
  - `borrado`, en casi todas las lecturas y en las escrituras sobre una fila existente (`update`, `updateField`, `limpiarCategorias` y `addCat`, también en `tabla_otra`): sin esa columna fallan con `text_bd_consulta`; y `activo`, en las que buscan filas activas (`getActivoById`, `getAllActivo`, `getBySlug`, `getByFkId`, `getUltimos`, `buscar`...).
  - `slug` en `getBySlug` y `getByCat`; `nombre` en `buscar` (con `contenido`), `getAllActivoOrdenAZ`, `getByCat` y `getCategoriasByFkId`; `fecha` en `getUltimos`; y `fecha_creacion` y `fk_id_usuario` en `getLastByUser`.
  - `name` lo usan `AdminHelper`, para la etiqueta del listado (`nombre`, si está vacío `name` y si no `email`) y las opciones de `select_dinamico`, y `Form`, para las de `select_dinamico` y `checkbox_dinamico`.
- `delete()` marca `borrado = 1`. `eliminarFila()` borra de verdad y solo es para deshacer un alta.
- `update()` y `updateField()` solo escriben si la fila tiene `borrado = 0`. Si no existe o está borrada, devuelven `false` con `text_fila_no_editable`, también en `edit/<id>/save` y en `editfield` de `AdminHelper`. `edit/<id>` de `AdminHelper` y de `users`, con una fila borrada o inexistente, no pinta el formulario: muestra el aviso, «No se encontró…» y un enlace a la lista. También tras guardar `borrado` a 1 en la edición.
- `limpiarCategorias()` y `addCat()` hacen lo mismo con la fila principal, que buscan en `tabla_otra`. `addCat()` tampoco añade una categoría de `tabla` borrada o inexistente: da `false` con `text_categoria_no_disponible`. Sin `tabla_otra` no escriben y dejan una línea `CFW:` en el log.
- Una fila borrada solo se recupera a mano en la BD. En `usuarios`, eso da el error 1062 si otro usuario activo tiene ese email.
- **`filtro()` guarda el texto ya codificado**: recorta los espacios, quita etiquetas y aplica `htmlentities` antes de escapar para SQL. Consecuencias:
  - Lo leído de la BD ya lleva entidades. `escapar()` no las codifica dos veces; `htmlspecialchars` a secas, sí.
  - Para comparar o volver a guardar un valor leído, decodifícalo antes con `html_entity_decode($v, ENT_QUOTES, "UTF-8")`, como hace `cuenta` con el email.
  - No se puede guardar HTML con `insert` ni `update`.
  - Un valor que el filtro deja vacío se ignora: `insert` no lo escribe y `update()` no vacía la columna. Para vaciarla, `updateField()` con `""`; un valor que el filtro vacía, en cambio, lo rechaza.
- No hay consultas preparadas. Un id pasa por `filtroId()`, un texto por `filtro()`, y un nombre de columna o un orden se comprueba contra `campos`. `getAllWhere()` recibe SQL literal: nunca datos del usuario sin filtrar.
- `select()` y los `get...` que devuelven filas dan `false` tanto sin filas como con error: para distinguirlo, mira `$orm->errores`, que recoge también el fallo de conexión. `getUltimos`, `buscar`, `getByCat`, `getByCatOrdenAZ`, `getCatById`, `getCategoriasByFkId` y `getLastByUser` devuelven `true` o `false` y dejan las filas en `$orm->resultado`.
- Cada objeto `Orm` abre su conexión: ciérrala con `cerrar()` al terminar.

## Administración con `AdminHelper`
- Una página que herede de `AdminHelper` da listado, alta, edición, vista, borrado y edición en la tabla de un `Orm`. Define `class` (el ORM), `slug` (el nombre de la página), `titulo`, `singular` y, si hacen falta, `template`, `template_listado`, `template_single`, `slug_admin`, `paginacion`, `categorias`, `categorias_orm`, `config_name`, `extensiones_permitidas` e `img_max_pixeles`. Se redefinen como `protected`: `private` da un error fatal.
- URL:
  - `admin/<slug>`, la página 1 del listado, y `admin/<slug>/pag/<n>`, la página `<n>`. Cada página tiene `paginacion` filas (un entero de 1 en adelante; sin él, `db.limit`), ordenadas por `id` y sin contar las borradas. Un `<n>` no numérico, 0 o negativo da la página 1, y uno mayor que la última, la última, sin error. Con más de una página, se añaden al final de `{content}` los enlaces Primera, Anterior, Siguiente y Última, con la plantilla `paginacion.tpl` y los textos `text_paginacion_*`.
  - `/admin/users` pagina igual, con `$paginacion` en una subclase de `users`; sus enlaces van siempre a `admin/users/pag/<n>`.
  - `admin/<slug>/form`, `admin/<slug>/edit/<id>` y `admin/<slug>/ver/<id>`.
  - Guardan solo por POST: `form/save` y `edit/<id>/save` (con otro método pintan el formulario), `delete/<id>` y `editfield`, con `field`, `value` e `id` (con otro método, estos dos responden 405).
- **`form` y `edit` pasan `$_POST` entero a `insert` y `update`.** Con `AdminHelper`, `campos` es lo que un administrador puede escribir con un POST hecho a mano, aunque la columna no esté en `campos_form`. Una columna que el formulario no deba tocar no puede estar en `campos` de un ORM que se use con `AdminHelper`. La excepción son los campos de tipo `select_desactivado` y `select_dinamico_desactivado`, buscados por su `name` y por su clave en `campos_form` (por la clave, salvo que otro campo no desactivado la use como `name`): `form` y `edit` los quitan de lo enviado y no los escriben nunca. En el alta, esa columna toma el valor por defecto de la BD, aunque el campo tenga `value`. Con `Usuarios`, no lo uses (ver «Seguridad» en [README.md](README.md)).
- `campos_form` describe el formulario: cada campo con `title`, `name` y `type`, y según el tipo `opciones`, `tabla` o `value`. `title` se pinta sin escapar.
- Tipos: `text`, `num`, `hidden`, `textarea`, `fecha`, `radio`, `checkbox`, `checkbox_single`, `checkbox_dinamico`, `select`, `select_desactivado`, `select_dinamico` y `select_dinamico_desactivado` (los dos dinámicos y `checkbox_dinamico`, con las opciones de la tabla `tabla`), `img`, `file`, `password_cifrado` y `cabecera`. Otro valor se pinta como `<input type="...">` con ese tipo. No uses `password`, que envía la contraseña en claro: los formularios con contraseña usan `password_cifrado`.
- `select` y `select_desactivado` toman `opciones` como `valor => texto`, al revés que `radio` y `checkbox`. Los dos desactivados se pintan como un select deshabilitado con el valor actual, con aspecto atenuado (`select:disabled` en `css/style.css`): el navegador no los envía y el servidor no los guarda.
- El título de cada fila se asocia a su campo: en los de texto, número, fecha, archivo, `textarea`, `select` y `password_cifrado`, con un `<label for="<name>">` (`html/label.tpl`), salvo si el campo no tiene `name`, que deja el título tal cual; en `radio`, `checkbox` y `checkbox_dinamico`, el título va en `<span id="<name>_grupo">` y las opciones en un `<fieldset class="form_grupo" aria-labelledby="<name>_grupo">`, sin borde. Esos ids pueden llevar `_2`, `_3`… (ver «Ids de `Form`»).
- Cada opción de `radio`, `checkbox` y `checkbox_dinamico` lleva `id="<name>_<n>"` (`n` desde 1, en el orden de `opciones`) y su texto en un `<label for>`, con la plantilla `html/label.tpl`. `checkbox_single` conserva `id` igual al `name`, también con etiqueta. Las casillas del listado llevan `id="elemento_<id>"`. Para `Form::getInput` propios, el 8.º parámetro, `$id`, es opcional y por defecto vale el `name`. Todos estos ids son la base: si ya están usados en la página, llevan `_2`, `_3`… (ver «Ids de `Form`»).
- Un `checkbox_single` debe estar también en `checkbox_singles` del ORM, o al desmarcarlo en la edición no se guarda. En el alta, un checkbox desmarcado no se envía.
- `editfield` solo escribe una columna por petición, de las que devuelve `camposEditables()`: las de `campos_form` que están en `campos`, salvo `id`, la clave primaria, `pass` y los tipos que no se editan en una celda (ver «Seguridad» en [README.md](README.md)).
- `ver/<id>` pinta las columnas de la fila sin escapar: vale para lo guardado con `filtro()`, no para datos escritos por otra vía.
- Plantillas:
  - `ver/<id>` usa `template_single` o, si no lo hay, `<slug>.tpl`. Si esa plantilla no existe, usa `element.tpl`.
  - `template`, `template_listado` y `template_single` admiten el nombre con o sin `.tpl` y con subcarpetas. Un nombre no válido o que no existe da la plantilla por defecto (`elements.tpl` en el listado, `element.tpl` en `ver`), sin 404.
- `categorias_orm` necesita `tabla` (las categorías), `tabla_otra` (la tabla principal), `tabla_cruce` y `cruce_id`. Si alguna categoría no se guarda, `form` y `edit` lo avisan en rojo con `text_categorias_problema` y los motivos del ORM, junto al aviso del alta o de la edición. Un `guardarCategorias()` redefinido avisa igual si devuelve `false` y deja los motivos en `$this->a_errores_categorias`.
- Subidas, con los campos `img` y `file`:
  - El campo debe estar en `campos`. El archivo se guarda con el id de la fila como nombre, más su extensión, y ese nombre se escribe en la columna.
  - La carpeta sale de la configuración fusionada, normalmente de `config/config-servidor.ini` (ver «Carpeta `data` y permisos» en [INSTALACION.md](INSTALACION.md)): `<config_name>.img.path` para las imágenes y `<config_name>.path` para los demás archivos, con la barra final, que aquí no se añade sola. Las imágenes necesitan además `<config_name>.img.width` y `<config_name>.img.height`: se reducen o amplían a ese ancho, sin pasar de ese alto.
  - Si es una imagen lo decide el contenido: un GIF, JPEG o PNG se guarda como imagen, con la extensión de su tipo real. Se rechaza si pasa de `img_max_pixeles` o falta la extensión GD.
  - En los demás archivos, la extensión la decide el contenido, nunca el MIME ni el nombre que envía el cliente. Debe ser texto UTF-8 sin bytes nulos: con alguna etiqueta (`<` seguido de una letra, `/` o `!`) es `tpl`; sin etiquetas y con al menos una regla `selector { propiedad: valor }`, `css` (sin contar los comentarios `/* */`, y la propiedad puede ser personalizada, como `--color`); lo demás se rechaza. Un CSS con algo que parezca una etiqueta, aunque sea en un comentario, se guarda como `tpl`, y un `.tpl` sin etiquetas o un CSS en Latin-1 con acentos se rechazan. Además, la extensión debe estar en `extensiones_permitidas` (por defecto, `tpl` y `css`), así que añadir otra ahí no sirve en estas subidas. Un contenido con `<?` se rechaza.
  - En el alta, una subida fallida deshace el alta: primero borra la fila con `eliminarFila()` y después los archivos guardados. Un archivo que no se pueda borrar deja una línea `CFW:` en el log, y si no se puede borrar la fila se conservan los archivos. Si la tabla no devuelve el id del alta (sin `AUTO_INCREMENT`), no se guardan las subidas ni las categorías y se avisa con `text_alta_sin_id`. En la edición, los datos ya quedan guardados. Un campo vacío no cuenta como enviado, tampoco uno múltiple (`name[]`) sin ningún archivo.
- `titulo` y `singular` no se traducen solos: dales valor con `t()` en el constructor.

## Sesión y contraseñas
- La sesión se lee en `$this->session->data`: `logueado` (el email, vacío sin sesión), `id_usuario`, `admin` (booleano) y `lang`. Tras cambiarla, `$this->session->actualiza()`.
- `isUserLogged()` e `isUserAdmin()` de `$this->session` miran solo la sesión: sirven para pintar, no para autorizar. Autorizan `exigirUsuario()` y `exigirAdmin()`, que lo comprueban en la BD.
- Una contraseña nunca viaja en un campo normal:
  - Al pintar el formulario, campo `password_cifrado` en `Form` (o `data-cifrar="<campo>"`, sin `name`, en una plantilla propia) y `prepararCifrado()`, que devuelve el aviso que se pinta junto al formulario.
  - Al recibirlo, `leerPassCifradas(array("<campo>", ...))`, que devuelve `campo => contraseña`, con `""` en los vacíos, o `false`. Con `false`, el mensaje es `textoFalloCifrado()`.
  - Se guarda con `pass_hash()`, que devuelve `false` si `pass_valida()` la rechaza (vacía, con un carácter nulo o de más de 72 bytes). `pass_valida()` solo dice si una contraseña nueva se puede guardar: el login la comprueba con `password_verify`, en `Usuarios::comprobarUserPass`.
- Lo que se pueda probar por fuerza bruta usa `intentos_bloqueado($clave)`, `intentos_fallo($clave)` e `intentos_limpiar($clave)`. Cuentan por clave e IP; con `false` como segundo argumento, solo por clave, como `cuenta`. Sin una clave privada válida o sin una carpeta de contadores utilizable no cuentan nada, ni bloquean: solo dejan una línea `CFW:` en el log. `intentos_limpiar` borra lo que haya en lugar del contador (archivo, FIFO o enlace, nunca su destino), salvo una carpeta.

## Configuración
- Hay dos archivos en `config/`, en la raíz, donde Apache solo lee, y el segundo pisa al primero clave a clave:
  - `config/config.ini`: lo que es igual en todos los servidores, de CFW y del sitio. Lo edita el sitio y se versiona. Nada secreto.
  - `config/config-servidor.ini`: lo de cada servidor, opcional y sin versionar: la base de datos, `urlbase`, `desarrollo`, las rutas de subida y lo que sea secreto. Los valores reales de producción solo van aquí.
- Lo que escribe la web va en `data/`, también en la raíz, nunca en `config/`.
- Las claves de CFW que falten en los dos toman un valor por defecto del código (`AppConf`); las que no lo tienen (`urlbase`, `email` y `db.*`) quedan vacías con un aviso en el log. Una clave nueva de CFW que se lee con `$this->config[...]` necesita un valor por defecto en `AppConf` o un aviso, y figurar en el apartado de su versión en [MIGRACION.md](MIGRACION.md).
- Las claves propias del sitio llevan un prefijo propio y van en `config/config.ini`, o en `config/config-servidor.ini` si dependen del servidor o son secretas.
- Se lee sin secciones: `$this->config["db.host"]`. Las secciones solo ordenan: la misma clave en dos secciones se pisa.
- Las comillas siguen las reglas de `parse_ini_file`, distintas de las de los textos, y un fichero mal formado o que no se leería tal como está escrito se ignora entero: ver el paso 3 de «Puesta en marcha» en [INSTALACION.md](INSTALACION.md).
- `urlbase` y `pathbase` terminan en barra, que se añade si falta. Las URL y rutas se construyen con ellas, no a mano.

## Estilo del código
- Tabuladores. Prefijos en las variables: `$s_` cadena, `$a_` array, `$b_` booleano, `$i_` entero.
- Los errores para quien administra van a `error_log("CFW: ...")`, sin datos personales; al visitante, un texto de `textos.ini`.
- Los `die`/`exit` tras responder son parte del diseño: `show()`, `showError()` y `redirigir()` cortan la petición.

## Trampas frecuentes
- Dar por protegida una página porque su URL empieza por `/admin/`.
- Procesar un POST en el constructor, o hacer algo en una página de administración antes de `exigirAdmin()`.
- Asignar a una plantilla un dato sin escapar, o confiar en `escapar()` para una URL, un atributo sin comillas o de evento, o poner comillas alrededor de `json_js()` (ver «Plantillas»).
- Dejar en `campos` una columna de permisos en un ORM que se usa con `AdminHelper`, o pasar `$_POST` entero a `update()` en esa tabla.
- Escapar o comparar un valor leído de la BD sin contar con que ya lleva entidades.
- Escribir en el `.htaccess` una regla pública sin anclar (`^contacto` en lugar de `^contacto/?$`), que coincide también con `/contactos` y otras URL que empiecen igual.
- Contar con el valor por defecto de un argumento en un método que se llama por URL: el que falta llega como `null`, también con barra final, y como `""` solo con `index.php?...&tres=`.
- Buscar desde JS o CSS un campo de `Form` por su `name` como id (`#email`): puede llevar `_2` (ver «Ids de `Form`»).
- Copiar entera en `cfw/specific/class/` u `orm/` una clase de la lista en lugar de heredar de su base (`FormCFW`, `PageCFW`…): la copia pierde las correcciones de CFW y, en `Form`, los ids únicos y el escapado.
- Escribir un `"` en un `.ini` de idioma, que lo invalida entero, o un `;` en un valor sin comillas.
- Crear un ORM o una clase en `specific` y no hacer el `require_once`.
- Definir en `specific/utils/functions.php` una función que ya existe en `general`: error fatal.
- Cambiar `cfw/general/` en el sitio: cada cambio choca con los de CFW al actualizar.
- Olvidar al actualizar CFW las claves nuevas de `config/config.ini` que lista [MIGRACION.md](MIGRACION.md).
- Copiar a `specific` un fichero de `general` para un cambio pequeño y olvidarlo en la siguiente actualización.
