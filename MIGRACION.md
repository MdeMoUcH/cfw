# Migrar un sitio basado en CFW a esta versión
Lo que hay que revisar al actualizar CFW en un proyecto ya construido con él. Para saber cómo funciona CFW, ver [README.md](README.md); para requisitos, permisos y despliegue, [INSTALACION.md](INSTALACION.md).

## Archivos que revisar a mano
Cada proyecto adapta estos ficheros, que no están en `specific` y no se actualizan solos:
- `cfw/config.ini`:
  - `langs` de `config.ini` queda sin uso.
  - Opción nueva y opcional `intentos.carpeta`, para guardar los contadores del límite de intentos en otra carpeta (ver «Límite de intentos» en «Despliegue» de [INSTALACION.md](INSTALACION.md)).
- El `.htaccess` raíz.
  - El bloque `<IfModule mod_headers.c>` de las cabeceras de seguridad es nuevo: cópialo y activa `mod_headers` (ver «Requisitos» en [INSTALACION.md](INSTALACION.md)). Sin el módulo no da error, pero los estáticos y los errores de Apache quedan sin esas cabeceras.
  - Apache solo pone cada cabecera si la respuesta no la trae ya.
- `db.sql`:
  - La columna `pass` debe ser al menos `varchar(255)`.
- `css/`, `js/` e `img/`.
- Las copias propias de páginas, clases, plantillas y textos en `cfw/specific/`.

## Peticiones y acceso
- Los formularios POST manuales necesitan el campo `csrf`. `Form` lo añade solo, y en una plantilla está disponible como `{csrf}`. Sin token la respuesta es 403.
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
- `main` solo pinta las plantillas de `$a_plantillas_permitidas`. Una subclase de `cfw/specific/` que use otras debe ampliar esa propiedad.
- En `AdminHelper::form`, una subida fallida deshace el alta con un `DELETE` físico (`Orm::eliminarFila`), y `guardarSubidas` devuelve `bool`.
- `pass_valida` y `pass_hash` son funciones globales nuevas: no pueden repetirse en `cfw/specific/utils/functions.php`.
- `t()` es global: una función `t` en `cfw/specific/utils/functions.php` da un fatal.
- `intentos_carpeta`, `intentos_archivo`, `intentos_abrir`, `intentos_bloqueado`, `intentos_fallo` e `intentos_limpiar` son funciones globales nuevas: no pueden repetirse en `cfw/specific/utils/functions.php`, porque PHP da un error fatal.

## Plantillas y portada
- Las plantillas ya no admiten PHP incrustado por `eval`.
- Los valores con `'` se guardan como `&#039;`. Escapa la salida con `escapar()`, que no codifica dos veces.
- La portada usa `portada.tpl` y `portada.ini`: `titulo` y `mensaje` de `main.ini` ya no se muestran en ella, y `/admin/main/init/main` da 404. `main.tpl` sigue en uso para `AdminHelper::delete`. Para personalizar la portada, usa `cfw/specific/lang/<código>/portada.ini` o `cfw/specific/templates/portada.tpl`. Una subclase de `main` con su propia lista de plantillas debe incluir `portada`. Un sitio derivado debe sustituir esta portada en producción, porque publica el framework y sus versiones.
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

## Contraseñas
- **Migración desde md5.** Los md5 existentes se aceptan una vez y se convierten a `password_hash` en el primer login correcto, sin que nadie cambie su contraseña. Las cuentas que nunca inicien sesión siguen en md5. El hash también se renueva si cambia el coste de `PASSWORD_DEFAULT`, por ejemplo al cambiar de versión de PHP.
- `comprobarUserPass` recibe la contraseña real, no su md5.
- `AdminHelper::editfield` ya no admite el campo `pass`.
- El login ya no envía el md5 de la contraseña, sino la contraseña cifrada (ver «Contraseñas» en [README.md](README.md)). Se elimina `js/js-md5-0.9.2.min.js`: si un proyecto la usa en `specific`, debe conservar su propia copia.
- La clave privada del cifrado se rechaza también si otros pueden leerla (por ejemplo 0644): los formularios muestran el aviso hasta corregirla con `chmod o-r` sobre el archivo.
- Los contadores del límite de intentos pasan de `sys_get_temp_dir()/cfw_intentos/` a `cfw/data/intentos/` (ver «Límite de intentos» en «Despliegue» de [INSTALACION.md](INSTALACION.md)):
  - Los bloqueos en curso se levantan. La carpeta antigua ya no se usa y se puede borrar.
  - Apache necesita poder escribir en `cfw/data` para crearla, o hay que crearla a mano. Si no se puede crear o no es segura, el límite queda desactivado, con una línea `CFW:` en el log por petición.
  - Una carpeta configurada con `intentos.carpeta` dentro de `/tmp`, o de otra carpeta con escritura para todos, no se acepta.

## JavaScript
- El login usa jQuery 4.0.0 (`js/jquery-4.0.0.min.js`); los formularios con contraseña, node-forge 1.4.2 (`js/forge-1.4.2.min.js`) y `js/cifrado.js`; y el listado de `AdminHelper`, Prototype 1.7.3 (`js/prototype-1.7.3.js`) con TableKit 1.3b. Prototype 1.7.3 lleva el aviso CVE-2020-27511 (ReDoS en `stripTags` y `unescapeHTML`), sin parche: no pases datos de usuario a esas funciones.
- Se retira jHtmlArea: desaparecen `$jhtmlarea` de `AdminHelper` y la plantilla `elements_html`.
- `main.js` solo conserva `sendLoginForm` y `devolver_mensaje`. Se eliminan `sendRegisterForm`, `comprobar_mail`, `sendAForm`, `muestraError`, `salir`, `sendFormUser` y los textos que usaban.
- `main.js` y `cifrado.js` pintan sus mensajes con las clases `mensaje mensaje_Red`, y se cargan como `main.js?v=3` y `cifrado.js?v=2`. Si el sitio tiene sus propias copias, actualízalas.
- Si activas la edición en línea de TableKit, configura `TableKit.options.editAjaxURI` y añade el `csrf` al POST: hoy no lo envía y la respuesta sería 403.
