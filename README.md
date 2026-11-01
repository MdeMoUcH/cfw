# cfw
Framework PHP

## Requisitos
- PHP 7.4, Apache con `mod_rewrite` y `.htaccess` activo, MySQL 5.7 y la extensión `mysqli`.
- La extensión GD (con JPEG y PNG) solo hace falta para subir imágenes. Sin ella, `saveImage` devuelve `false`.

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
