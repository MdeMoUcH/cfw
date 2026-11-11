# Migrar un sitio basado en CFW a esta versión
Lo que hay que revisar al actualizar CFW en un proyecto ya construido con él. Para saber cómo funciona CFW, ver [README.md](README.md); para requisitos, permisos y despliegue, [INSTALACION.md](INSTALACION.md).

Esta versión exige PHP 8.2, la extensión `openssl` (sin ella no hay login) y Apache 2.4.10 con `mod_headers`, y se recomienda la extensión `posix`. Apache necesita escritura en `cfw/data` para generar la clave privada del cifrado: si no la tiene, el login muestra un aviso en lugar del formulario. Ver «Requisitos» y «Clave privada del cifrado» en [INSTALACION.md](INSTALACION.md).

## Archivos que revisar a mano
Cada proyecto adapta estos ficheros, que no están en `specific` y no se actualizan solos:
- `cfw/config.ini`:
  - `langs` de `config.ini` queda sin uso.
  - Opción nueva y opcional `intentos.carpeta`, para guardar los contadores del límite de intentos en otra carpeta (ver «Límite de intentos» en «Despliegue» de [INSTALACION.md](INSTALACION.md)).
- El `.htaccess` raíz.
  - El bloque `<IfModule mod_headers.c>` de las cabeceras de seguridad es nuevo: cópialo y activa `mod_headers` (ver «Requisitos» en [INSTALACION.md](INSTALACION.md)). Sin el módulo no da error, pero los estáticos y los errores de Apache quedan sin esas cabeceras.
  - Apache solo pone cada cabecera si la respuesta no la trae ya.
  - Responde 403 a los archivos ocultos (salvo `.well-known`), a todo `cfw/` y a los `.ini`, `.sql`, `.tpl` y `.md`.
  - Se quita la regla que redirigía `/uploads` a `http://localhost/`. Si tu sitio guarda en `uploads/` archivos que no deben descargarse, llévalos fuera de la raíz web o bloquéalos con su propia regla (por ejemplo, `RewriteRule ^uploads(/|$) - [F,NC,L]`).
  - Se quitan las reglas de `/login/register` y `/login/restorepassword`, que no tenían método.
- `cfw/data/.htaccess`, nuevo: cópialo también.
- `db.sql`:
  - La columna `pass` debe ser al menos `varchar(255)`.
- `css/`, `js/` e `img/`.
- Las copias propias de páginas, clases, plantillas y textos en `cfw/specific/`.

## Peticiones y acceso
- El login exige `activo = 1`: las cuentas con `activo = 0` dejan de poder entrar.
- El `db.sql` anterior creaba el administrador `m@m.es` con una contraseña trivial y conocida y `activo` a 0. Bórralo o cámbiale la contraseña al migrar.
- `cuenta`, `users`, `idiomas` y las páginas de `AdminHelper` comprueban en la BD, en cada petición, que el usuario sigue activo y, en administración, que sigue siendo administrador: si no, la sesión se cierra. Si la BD falla, responden 503.
- Las subclases de `AdminHelper` exigen un administrador desde el constructor (`exigirAdmin`), e `index.php` no ejecuta sus métodos si no se ha comprobado:
  - Las que no llamen a `parent::__construct()` ni a `exigirAdmin()` en su constructor pasan a responder 403 a todos. Antes quedaban abiertas.
  - Una subclase usada así para páginas públicas debe pasar a heredar de `Page`.
  - Un `AdminHelper` propio en `cfw/specific/class/` debe seguir llamando a `exigirAdmin()` en su constructor.
- `users` e `idiomas` exigen también el administrador desde el constructor, e `index.php` lo comprueba como en `AdminHelper` (implementan la interfaz nueva `PaginaAdmin`):
  - Una subclase de `users` o `idiomas` con otro nombre cuyo constructor no llame a `parent::__construct()` pasa a responder 403 a todos.
  - Una copia propia de `users` o `idiomas` en `cfw/specific/pages/` no implementa `PaginaAdmin` y no recibe la comprobación: parte de la nueva.
  - Un POST anónimo sin `csrf` a `users` recibe ahora un 302 al login en lugar de un 403, porque la revalidación del administrador va en el constructor, como en `AdminHelper` e `idiomas`.
- Los formularios POST manuales necesitan el campo `csrf`. `Form` lo añade solo, y en una plantilla está disponible como `{csrf}`. Sin token la respuesta es 403.
- El 403 por token CSRF no válido lo pinta siempre `ErrorCFW`, no el `showError` de la página; una página con `showError` redefinido sin terminar ya no ejecuta el método.
- `login/logout` y `delete` de `AdminHelper` solo actúan por POST.
- Los métodos de `App` y `Page` (y sus redefiniciones) ya no son accesibles por URL.
- `get_ip` usa solo `REMOTE_ADDR` y deja de usar `X-Forwarded-For`.
- `index.php` solo despacha clases que hereden de `Page`. Las demás dan 404, también las de `cfw/specific/pages/`.
- `index.php` envía `X-Frame-Options: DENY`, `Content-Security-Policy: frame-ancestors 'none'`, `X-Content-Type-Options: nosniff` y `Referrer-Policy: strict-origin-when-cross-origin`:
  - Ninguna página se puede enmarcar, tampoco desde el propio sitio. Para enmarcar las propias, redefine en la página `X-Frame-Options` y `Content-Security-Policy` con `header()`, por ejemplo `SAMEORIGIN` y `frame-ancestors 'self'`. Apache no las pisa.
  - Una CSP propia enviada desde PHP sustituye a la del framework y tiene que incluir `frame-ancestors`.
  - Con `nosniff`, los scripts y estilos servidos con un `Content-Type` incorrecto dejan de cargarse.

## Páginas, clases y funciones
- `saveImage`, `saveFile`, `guardarCategorias` y `load_template` pasan a `protected`.
- `saveFile` solo acepta `tpl` y `css`, salvo que se redefina `$extensiones_permitidas`. Los nombres subidos se normalizan y `saveImage` pone siempre la extensión del tipo real.
- Se eliminan `comprobarInvitado`, `getLastUserByFkIdUsuario` y `getUsersByFkIdUsuario` de `Usuarios`.
- PHP 8.2 da `Deprecated` con las propiedades dinámicas: las subclases de `cfw/specific/` deben declarar las suyas, con una visibilidad compatible con la del padre. `private` da un fatal, y `protected` también si el padre la declara `public`, como `$tabla` en `Orm` o `$vars` en `Page`.
- Se eliminan la función global `saveImage` y `cfw/general/utils/images.php` (`saveImageProductos`). Usa `AdminHelper::saveImage`.
- `main` solo pinta las plantillas de `$a_plantillas_permitidas`. Una `main` propia en `cfw/specific/pages/main.php` sustituye entera a la de `general`, sin heredar nada (ver los límites de «Qué se puede redefinir en `specific`» en [README.md](README.md)), así que debe partir de la `main` nueva y declarar su propia `$a_plantillas_permitidas`, con `portada`: una copiada de la versión anterior no tiene esa lista. Ampliar la propiedad solo vale en una subclase de `main` con otro nombre.
- `Page` tiene métodos `protected` nuevos: `redirigir`, `guardarFlash`, `leerFlash`, `prepararCifrado`, `leerPassCifradas` y `textoFalloCifrado`. Un método con el mismo nombre en una subclase los redefine, y con una firma o una visibilidad incompatibles da un error fatal.
- `Page` tiene también los métodos `final` `adminRevalidado` (público), `exigirUsuario` y `exigirAdmin` (protegidos): una subclase que declare un método con alguno de esos nombres da un error fatal (500 en esa página). `exigirUsuario` y `exigirAdmin` terminan la petición aunque `redirigir` o `showError`, redefinidos, no lo hagan.
- Un `Page` propio en `cfw/specific/class/` copiado de una versión anterior no tiene `adminRevalidado`, y eso da un error fatal en todas las páginas de `AdminHelper` y de `PaginaAdmin`: parte del `Page` nuevo.
- `login::init()` ya no recibe un mensaje (antes `init($s_mensaje = "")`). Para llegar al login con un aviso, guarda con `guardarFlash` la clave de `textos.ini` del aviso, no el texto, y usa `redirigir`, como hace `cuenta`.
- `Orm` solo acepta ids formados por dígitos en `getById`, `getActivoById`, `delete`, `update`, `updateField` y los demás métodos que reciben un id: con otro valor devuelven `false` y añaden un error a `errores`. `update` y `updateField` no escriben la clave primaria, y `updateField` solo admite columnas de `$campos`.
- En `AdminHelper::form`, una subida fallida deshace el alta con un `DELETE` físico (`Orm::eliminarFila`), y `guardarSubidas` devuelve `bool`.
- `PaginaAdmin` es una interfaz nueva: una clase propia con ese nombre da un error fatal.
- La etiqueta del listado de `AdminHelper` es `nombre`, si está vacío `name` y si no `email`, para cualquier slug. Antes, con el slug `users`, era siempre el `email`: un `AdminHelper` con ese slug y filas con `nombre` o `name` deja de mostrarlo.
- `pass_valida` y `pass_hash` son funciones globales nuevas: no pueden repetirse en `cfw/specific/utils/functions.php`.
- `t()` es global: una función `t` en `cfw/specific/utils/functions.php` da un fatal.
- `intentos_carpeta`, `intentos_archivo`, `intentos_abrir`, `intentos_bloqueado`, `intentos_fallo` e `intentos_limpiar` son funciones globales nuevas: no pueden repetirse en `cfw/specific/utils/functions.php`, porque PHP da un error fatal.

## Plantillas y portada
- Las plantillas ya no admiten PHP incrustado por `eval`.
- Los valores con `'` se guardan como `&#039;`. Escapa la salida con `escapar()`, que no codifica dos veces.
- La portada usa `portada.tpl` y `portada.ini`: `titulo` y `mensaje` de `main.ini` ya no se muestran en ella, y `/admin/main/init/main` da 404. `main.tpl` sigue en uso para `AdminHelper::delete`. Para personalizar la portada, usa `cfw/specific/lang/<código>/portada.ini` o `cfw/specific/templates/portada.tpl`. Una `main` propia en `cfw/specific/pages/main.php` debe declarar su lista de plantillas con `portada` (ver «Páginas, clases y funciones»). Un sitio derivado debe sustituir esta portada en producción, porque publica el framework y sus versiones.
- El formulario de datos de `cuenta` pasa a `web/cuenta_usuario.tpl`, con `data-cifrar` y sin `name` en las contraseñas. `web/cuenta.tpl` lo pinta con `{aviso_cifrado}` y `{form_usuario}`. Si el sitio tiene su propia copia de `web/cuenta.tpl` en `cfw/specific/templates/`, actualízala: con la antigua no se ve el formulario nuevo, o las contraseñas se envían en claro y el servidor responde «formulario caducado».

## Aspecto
- `cascara.tpl` lleva `<!DOCTYPE html>`, así que el sitio pasa de modo quirks a modo estándar y cambia el pintado de tablas y alturas de línea. También añade `meta viewport`, `link rel="icon"` a `img/favicon.svg`, el nombre de la cabecera enlazado a la portada y `css/style.css?v=2`. Actualiza las copias de `cfw/specific/templates/cascara.tpl`.
- `css/style.css` se reescribe. La regla `body` pasa de `tablas.css` a `style.css`, y `tablas.css` se carga con `?v=2`. Fusiona los cambios en el CSS propio del sitio. Clases nuevas: `mensaje`, `mensaje_Red`, `mensaje_Green`, `mensaje_Blue`, `form_cuenta`, `form_salir` y `acciones`.
- Plantillas:
  - `usuarios_admin.tpl` y `errores.tpl` pierden las clases `panel-title`, `fondo_claro` y `panel-body`.
  - `usuarios.tpl` y `elements.tpl` usan `h1` para el título.
  - `{content}` y `{error}` van dentro de un `div`, no de un `p`. Los errores de `ErrorCFW` salen en `<p>`, no en `<h2>`.
  - `usuarios.tpl` y `usuarios_admin.tpl` pintan `{boton_salir}` (nueva `html/form_salir.tpl`), que `Page::show` solo rellena con sesión, salvo que la plantilla ya lo defina. `text_logout` deja de usarse en `general`.
  - `html/p_color.tpl` añade las clases `mensaje mensaje_{color}`.
  - `web/cuenta_usuario.tpl` ya no lleva estilos en línea.
  - Actualiza las copias de estas plantillas en `cfw/specific/templates/`.
- `get_menu` marca como activos Cuenta, Usuarios e Idiomas, y `/login` marca «Login» en lugar de «Inicio».
- Cada página de `general` pone su `page_title`.
- Textos: `text_enlace_admin`, `text_enlace_lista`, `text_enlace_nuevo`, `text_salir` y `text_crear_usuario` empiezan con mayúscula, y `text_titulo_form` pasa a «Formulario de {titulo}», lo que cambia también los títulos de los formularios de `AdminHelper`. Hay una clave nueva, `text_titulo_form_usuarios`. Las claves de `cfw/specific/lang/` siguen ganando.
- `users` tiene `$titulo_form` para el título del formulario: una subclase que cambie `$titulo` debe cambiarlo también.
- `img/favicon.svg` es el icono de CFW: cada sitio pone el suyo.

## Idiomas
- Los `.ini` de idioma se fusionan por clave: `general` y `specific` de `lang_default`, y encima los del idioma actual. Un `specific/lang/<default>` ya no gana a `general/lang/<idioma>` entero, solo aporta sus claves. `text_error_usuario_mail` pasa a `textos.ini`.
- `get_array_text` y `cfw/general/lang/es/menu.php` ya no se usan, aunque el fichero sigue en el árbol. Ya no se carga PHP desde `lang/`: un `menu.php` propio deja de aplicarse. El menú usa `{text_menu_inicio}` y `{text_menu_login}`.
- `cfw/specific/lang/{es,en}/main.ini` siguen en el árbol y tienen prioridad sobre `general` en su idioma: cambiar `titulo` o `mensaje` en `cfw/general/lang/<código>/main.ini` no tiene efecto mientras existan esas claves en `specific`.
- Los `.ini` de idioma se leen con `INI_SCANNER_RAW`. Un fichero con una comilla sin cerrar o una línea sin clave se ignora y deja una línea `CFW:` en el log.
- `titulo` y `singular` de las subclases de `AdminHelper` no se traducen solos: los define cada sitio.
- `cfw/data/idiomas.ini` ya no se lee si es un enlace (por ejemplo, a una carpeta compartida de despliegue): el multiidioma se desactiva, `/admin/idiomas` muestra el aviso y queda una línea `CFW:` en el log en cada petición. Copia su contenido a un archivo normal o vuelve a guardar desde `/admin/idiomas` (ver «Multiidioma» en «Despliegue» de [INSTALACION.md](INSTALACION.md)).

## Contraseñas
- **Migración desde md5.** Los md5 existentes se aceptan una vez y se convierten a `password_hash` en el primer login correcto, sin que nadie cambie su contraseña. Las cuentas que nunca inicien sesión siguen en md5. El hash también se renueva si cambia el coste de `PASSWORD_DEFAULT`, por ejemplo al cambiar de versión de PHP.
- `comprobarUserPass` recibe la contraseña real, no su md5.
- `AdminHelper::editfield` ya no admite el campo `pass`.
- El login ya no envía el md5 de la contraseña, sino la contraseña cifrada (ver «Contraseñas» en [README.md](README.md)). Se elimina `js/js-md5-0.9.2.min.js`: si un proyecto la usa en `specific`, debe conservar su propia copia.
- La clave privada del cifrado se rechaza también si otros pueden leerla (por ejemplo 0644): los formularios muestran el aviso hasta corregirla. Hazlo desde la raíz del sitio como el usuario de PHP, que es su propietario, y no como root (ver «Órdenes como Apache» en «Carpeta `cfw/data` y permisos» de [INSTALACION.md](INSTALACION.md)): `sudo -u www-data chmod o-r cfw/data/clave_privada.pem`, o la ruta de `cifrado.clave_privada` si la usas. Si falla con «Operation not permitted», la clave no es de ese usuario: bórrala y genérala de nuevo como Apache (ver «Permisos exigidos» en «Clave privada del cifrado» de [INSTALACION.md](INSTALACION.md)).
- Los contadores del límite de intentos pasan de `sys_get_temp_dir()/cfw_intentos/` a `cfw/data/intentos/` (ver «Límite de intentos» en «Despliegue» de [INSTALACION.md](INSTALACION.md)):
  - Los bloqueos en curso se levantan. La carpeta antigua ya no se usa y se puede borrar.
  - Apache necesita poder escribir en `cfw/data` para crearla, o hay que crearla a mano, como Apache (ver «Creación» en «Límite de intentos» de [INSTALACION.md](INSTALACION.md)). Si no se puede crear o no es segura, el límite queda desactivado, con una línea `CFW:` en el log en cada intento de login o de guardado en `cuenta` con la contraseña actual.
  - Una carpeta configurada con `intentos.carpeta` dentro de `/tmp`, o de otra carpeta con escritura para todos, no se acepta.
- Los contadores del límite de intentos cambian de nombre: en lugar del md5 de la IP y el email (o del usuario, en `cuenta`), son un HMAC-SHA256 con un secreto derivado de la clave privada del cifrado.
  - Al actualizar se levantan los bloqueos en curso. Los contadores antiguos se ignoran, y la limpieza que se hace de vez en cuando al anotar un fallo los borra como a cualquier contador de más de 15 minutos.
  - Bórralos a mano después de desplegar el código nuevo: con la carpeta en 0750 o 0755 se pueden listar, y su md5 se puede invertir hasta que llegue esa limpieza. Son los archivos normales de la carpeta de los contadores (`cfw/data/intentos`, o la de `intentos.carpeta`) cuyo nombre tiene 32 caracteres hexadecimales; los nuevos tienen 64. Con GNU `find`, desde la raíz del sitio y como el usuario de PHP, que es el propietario de la carpeta, y no como root (ver «Órdenes como Apache» en «Carpeta `cfw/data` y permisos» de [INSTALACION.md](INSTALACION.md)); cambia la ruta si usas `intentos.carpeta` (`find` no sigue enlaces y `-type f` deja fuera todo lo que no sea un archivo normal):
    ```
    sudo -u www-data find cfw/data/intentos -maxdepth 1 -type f -regextype posix-extended -regex '.*/[0-9a-f]{32}' -delete
    ```
  - Rotar la clave privada también pone los contadores a cero. Sin una clave privada válida, el límite queda desactivado, con una línea `CFW:` en el log.

## JavaScript
- El login usa jQuery 4.0.0 (`js/jquery-4.0.0.min.js`); los formularios con contraseña, node-forge 1.4.2 (`js/forge-1.4.2.min.js`) y `js/cifrado.js`; y el listado de `AdminHelper`, Prototype 1.7.3 (`js/prototype-1.7.3.js`) con TableKit 1.3b. Prototype 1.7.3 lleva el aviso CVE-2020-27511 (ReDoS en `stripTags` y `unescapeHTML`), sin parche: no pases datos de usuario a esas funciones.
- Se retira jHtmlArea: desaparecen `$jhtmlarea` de `AdminHelper` y la plantilla `elements_html`.
- `main.js` solo conserva `sendLoginForm` y `devolver_mensaje`. Se eliminan `sendRegisterForm`, `comprobar_mail`, `sendAForm`, `muestraError`, `salir`, `sendFormUser` y los textos que usaban.
- `main.js` y `cifrado.js` pintan sus mensajes con las clases `mensaje mensaje_Red`, y se cargan como `main.js?v=3` y `cifrado.js?v=2`. Si el sitio tiene sus propias copias, actualízalas.
- La edición en línea de TableKit del listado de `AdminHelper` sigue desactivada en el núcleo. Si la activas (tabla con clase `editable` y cabeceras sin `noedit` cuyo `id` es la columna), `listado_pro.tpl` añade el `csrf` al POST y, si `TableKit.options.editAjaxURI` sigue en su valor por defecto (`/`), lo dirige a `admin/<slug>/editfield`; un `editAjaxURI` ya configurado se conserva. Un `listado_pro.tpl` propio en `cfw/specific/templates/` debe copiar el segundo `<script>` del nuevo: sin él, la respuesta es 403.
