# cfw
Framework PHP

## Requisitos
- PHP 8.2 o superior (probado en 8.2, 8.3, 8.4 y 8.5), Apache con `mod_rewrite` y `.htaccess` activo, MySQL 5.7 y la extensión `mysqli`.
- La extensión GD (con JPEG y PNG) solo hace falta para subir imágenes. Sin ella, `saveImage` devuelve `false`.
- La extensión `mbstring` solo hace falta para `filtro_mysql_utf8`.

## Puesta en marcha
1. Carga `db.sql`. No crea ningún usuario.
2. Crea el primer administrador con el `INSERT` de ejemplo comentado en `db.sql`, con tu email y tu contraseña.
3. Revisa `cfw/config.ini`. El archivo está versionado: pon los datos reales solo en el servidor y no los subas al repositorio. `desarrollo = true` muestra errores y detalles; no lo actives en un servidor público.
4. Usa siempre HTTPS. El framework no lo fuerza.

## Riesgo conocido: md5
Las contraseñas se guardan en md5 sin sal, y el login las envía ya en md5 desde el navegador. Por eso el hash almacenado equivale a la contraseña: quien lea la base de datos puede entrar sin descifrarlo. `cuenta` y `users` envían la contraseña en claro y calculan el md5 en el servidor; ambas vías guardan el mismo valor. Hay que migrar a `password_hash` antes de usarlo con datos reales.

## Notas de migración para sitios existentes
- Los formularios POST manuales necesitan el campo `csrf`. `Form` lo añade solo, y en una plantilla está disponible como `{csrf}`. Sin token la respuesta es 403.
- `login/logout` y `delete` de `AdminHelper` solo actúan por POST.
- Los métodos de `App` y `Page` (y sus redefiniciones) ya no son accesibles por URL.
- `saveImage`, `saveFile`, `guardarCategorias` y `load_template` pasan a `protected`.
- `saveFile` solo acepta `tpl` y `css`, salvo que se redefina `$extensiones_permitidas`. Los nombres subidos se normalizan y `saveImage` pone siempre la extensión del tipo real.
- Las plantillas ya no admiten PHP incrustado por `eval`.
- Se eliminan `comprobarInvitado`, `getLastUserByFkIdUsuario` y `getUsersByFkIdUsuario` de `Usuarios`.
- Los valores con `'` se guardan como `&#039;`. Escapa la salida con `escapar()`, que no codifica dos veces.
- `get_ip` usa solo `REMOTE_ADDR` y deja de usar `X-Forwarded-For`.
- Un `main.ini` en `cfw/specific/lang/` sustituye al general: tiene que incluir `text_error_usuario_mail`.
- PHP 8.2 da `Deprecated` con las propiedades dinámicas: las subclases de `cfw/specific/` deben declarar las suyas, con una visibilidad compatible con la del padre. `private` da un fatal, y `protected` también si el padre la declara `public`, como `$tabla` en `Orm` o `$vars` en `Page`.
- Se eliminan la función global `saveImage` y `cfw/general/utils/images.php` (`saveImageProductos`). Usa `AdminHelper::saveImage`.
- `index.php` solo despacha clases que hereden de `Page`. Las demás dan 404, también las de `cfw/specific/pages/`.
- `main` solo pinta las plantillas de `$a_plantillas_permitidas`. Una subclase de `cfw/specific/` que use otras debe ampliar esa propiedad.
- En `AdminHelper::form`, una subida fallida deshace el alta con un `DELETE` físico (`Orm::eliminarFila`), y `guardarSubidas` devuelve `bool`.
- El login usa jQuery 4.0.0 y js-md5 0.9.2 (`js/jquery-4.0.0.min.js`, `js/js-md5-0.9.2.min.js`), y el listado de `AdminHelper`, Prototype 1.7.3 (`js/prototype-1.7.3.js`) con TableKit 1.3b. Prototype 1.7.3 lleva el aviso CVE-2020-27511 (ReDoS en `stripTags` y `unescapeHTML`), sin parche: no pases datos de usuario a esas funciones.
- jQuery y Prototype no deben cargarse en la misma página.
- Se retira jHtmlArea: desaparecen `$jhtmlarea` de `AdminHelper` y la plantilla `elements_html`.
- `main.js` solo conserva `sendLoginForm` y `devolver_mensaje`. Se eliminan `sendRegisterForm`, `comprobar_mail`, `sendAForm`, `muestraError`, `salir`, `sendFormUser` y los textos que usaban.
- El md5 del login ahora coincide con el de PHP también en contraseñas con emoji, que antes no podían iniciar sesión.
- Si activas la edición en línea de TableKit, configura `TableKit.options.editAjaxURI` y añade el `csrf` al POST: hoy no lo envía y la respuesta sería 403.
