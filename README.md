# cfw
Framework PHP

## Qué es
CFW es un framework MVC en PHP que da una base con opciones básicas: portada, login, cuenta, gestión de usuarios e idiomas. Cada proyecto se construye encima sin modificar esa base.

- **`cfw/general/`** es el funcionamiento base de CFW.
- **`cfw/specific/`** es la parte de cada proyecto. En este repositorio solo está su estructura, y lo que pongas ahí tiene prioridad sobre `general`.

Las piezas MVC son:
- **Controlador:** las clases de `pages/`, que heredan de `Page`.
- **Modelo:** las clases de `orm/`, que heredan de `Orm`.
- **Vista:** las plantillas `.tpl` de `templates/`, con `Template`.

## Enrutamiento
Una petición se traduce en clase, método y hasta dos argumentos:

```
/admin/clase/metodo/arg1/arg2  ->  index.php?uno=clase&dos=metodo&tres=arg1&cuatro=arg2
```

1. `index.php` busca `cfw/specific/pages/<clase>.php` y, si no existe, `cfw/general/pages/<clase>.php`. Si no se indica clase, usa `class.default` de `config.ini`.
2. El fichero debe definir una clase con el mismo nombre, en minúsculas, números y `_`, que herede de `Page`.
3. Sin método se ejecuta `init()`. Con método, se ejecuta `$page->metodo($arg1, $arg2)`.
4. Si algo falla, responde 404 con `ErrorCFW`. Los detalles solo se muestran con `desarrollo = true`.

Solo son accesibles por URL los métodos públicos declarados en la página. Los de `App` y `Page`, los estáticos y los que empiezan por `__` no lo son. Los POST exigen un token CSRF válido.

El `.htaccess` raíz solo define las rutas `/admin/...` y `/login`. Otra URL pública, como `/contacto`, necesita su propia regla en ese fichero o usar `index.php?uno=contacto`.

## Qué se puede redefinir en `specific`

| Carpeta | Efecto |
|---|---|
| `pages/` | Sustituye a la página de `general` con el mismo nombre, o añade una nueva. |
| `class/` | Sustituye a la clase de `general` con el mismo nombre. |
| `orm/` | Sustituye a `Usuarios` o `DinamicOrm`. |
| `templates/` | Sustituye a la plantilla con la misma ruta. |
| `lang/<código>/` | Se fusiona clave a clave con los textos de `general`, con prioridad sobre ellos dentro del mismo idioma. |
| `utils/functions.php` | Se carga además del de `general`. |

Límites:
- La sustitución es del fichero entero. Una clase no puede heredar de la de `general` con su mismo nombre, así que hay que copiarla o crear otra con nombre distinto.
- No hay carga automática. `cfw/includes.php` solo carga una lista fija de clases y ORM. Las nuevas se incluyen con `require_once` desde la página que las use.
- Una función de `specific/utils/functions.php` no puede llamarse igual que una de `general`: PHP da un error fatal.

## Qué queda fuera de `specific`
Cada proyecto también adapta estos ficheros, que no están en `specific`: `cfw/config.ini`, el `.htaccess` raíz, `db.sql`, `css/`, `js/` e `img/`. Al actualizar CFW en un proyecto hay que revisarlos a mano. `cfw/datos/` guarda lo que escribe la aplicación. Su contenido no se versiona, salvo su `.htaccess`.

## Requisitos
- PHP 8.2 o superior (probado en 8.2, 8.3, 8.4 y 8.5), Apache con `mod_rewrite` y `.htaccess` activo, MySQL 5.7 y las extensiones `mysqli` y `openssl`.
- La extensión GD (con JPEG y PNG) solo hace falta para subir imágenes. Sin ella, `saveImage` devuelve `false`.
- La extensión `mbstring` solo hace falta para `filtro_mysql_utf8`.

## Puesta en marcha
1. Carga `db.sql`. No crea ningún usuario.
2. Crea el primer administrador con el `INSERT` de ejemplo comentado en `db.sql`, con tu email y el hash de tu contraseña generado con `password_hash`.
3. Revisa `cfw/config.ini`. El archivo está versionado: pon los datos reales solo en el servidor y no los subas al repositorio. `desarrollo = true` muestra errores y detalles; no lo actives en un servidor público.
4. Usa siempre HTTPS. El framework no lo fuerza, y el cifrado de las contraseñas no lo sustituye.

## Idiomas
Los textos están en `cfw/general/lang/<código>/` (`textos.ini` y un `.ini` por plantilla). Los de `cfw/specific/lang/<código>/` tienen prioridad, clave a clave.

La opción de multiidioma se gestiona en `/admin/idiomas` y se guarda en `cfw/datos/idiomas.ini`, que no se versiona. Con ella activa, cada usuario elige su idioma en `cuenta` y los visitantes anónimos reciben el de `Accept-Language`, entre los activos. Si no, todo sale en `lang_default`, que siempre está activo.

Antes de usarla, da a Apache permiso sobre la carpeta. La aplicación crea el fichero la primera vez que guardas:
```
chgrp www-data cfw/datos && chmod 770 cfw/datos
```
- Repite esta orden tras un clon nuevo, porque git no conserva el grupo ni el modo. `chgrp` puede requerir privilegios.
- Apache no debe ser propietario de `cfw/general/lang/` ni tener escritura sobre ella, tampoco de grupo o recursiva. Los textos se pintan sin escapar: quien pueda escribir ahí puede inyectar código en todo el sitio, incluida la administración.
- Conserva el fichero entre despliegues. Un `rsync --delete` o un `git clean -X` lo borran: el multiidioma se desactiva y `/admin/idiomas` muestra un aviso.
- La opción está pensada para un solo servidor: con varios, el fichero no se comparte entre ellos.

Para añadir un idioma, crea `cfw/general/lang/<código>/textos.ini`, con un código de dos letras minúsculas y `nombre_idioma`; las claves que falten salen en `lang_default`. Los valores van entre comillas dobles y no pueden contener comillas dobles. Si el valor lleva HTML o datos, escápalos antes de pasarlos a `t()`.

## Contraseñas
Se guardan con `password_hash` (`PASSWORD_DEFAULT`) y se comprueban con `password_verify`. La columna `pass` debe ser al menos `varchar(255)`.

**Envío cifrado.** El navegador no envía la contraseña en claro ni en md5:
- `js/cifrado.js` cifra `1|<nonce>|<campo>|<contraseña>` con RSA-OAEP (SHA-1) y la clave pública del servidor, usando node-forge. El servidor la descifra con openssl y comprueba que el nonce es de su sesión, no ha caducado (30 minutos) y no se ha usado.
- Cada formulario pintado lleva un nonce de un solo uso: un POST capturado no se puede reenviar, y un formulario antiguo o enviado dos veces muestra «formulario caducado».
- Los campos son del tipo `password_cifrado` de `Form` (o `data-cifrar="<campo>"` en una plantilla). No tienen `name`: si el cifrado falla, no se envía nada. En el controlador, `Page::prepararCifrado()` prepara la página y `Page::leerPassCifradas()` devuelve las contraseñas descifradas.
- Sin JavaScript no se puede iniciar sesión, cambiar la contraseña ni dar de alta usuarios.
- **Límites:** no sustituye a HTTPS. Sin él, quien controle la red puede modificar el JavaScript o la clave pública que se sirven y capturar la contraseña, o robar la cookie de sesión. Con la sesión de un administrador se pueden cambiar las contraseñas de otros usuarios en `/admin/users` sin reautenticarse.

**Clave privada:** `cfw/datos/clave_privada.pem` por defecto, RSA de 3072 bits.
- Se genera sola en el primer uso, con permisos 0600, si Apache puede escribir en `cfw/datos/` (ver «Idiomas»). Si no puede, o la clave no es válida, se muestra un aviso en lugar de los formularios con contraseña (en `users` solo se puede editar sin cambiarla) y el log una línea `CFW:`; un archivo no válido no se sobrescribe.
- También se puede generar a mano, como el usuario de Apache: `sudo -u www-data openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -out cfw/datos/clave_privada.pem`.
- **Permisos exigidos.** Si no se cumplen, la clave se rechaza sin sobrescribirla (aviso en los formularios y una línea `CFW:` en el log):
  - El archivo debe ser un archivo normal, no un enlace, cuyo propietario sea el usuario con el que corre PHP (`www-data`, o el de la cuenta con PHP-FPM o suEXEC). Ni el grupo ni otros pueden tener escritura sobre él: 0600 o 0400. Para corregirlo: `chown www-data cfw/datos/clave_privada.pem && chmod 600 cfw/datos/clave_privada.pem`.
  - La carpeta no puede tener escritura para todos (por ejemplo 777 o `/tmp`). Se admite la de grupo, como el 775 de un clon o el 770 con el grupo de Apache de «Idiomas», y cualquier propietario, también root. Para corregirlo: `chmod o-w cfw/datos`.
  - El propietario solo se comprueba con la extensión `posix`, que se recomienda tener. Sin ella la garantía es más débil: se acepta una clave de cualquier propietario si cumple el resto (archivo normal, sin escritura de grupo ni de otros, carpeta sin escritura para todos), y al generarla queda una línea `CFW:` en el log que lo avisa. En Windows no se comprueban propietario ni permisos, solo que no sea un enlace.
- Para rotarla, bórrala: se crea otra en la siguiente petición. Solo fallan una vez los formularios que estaban abiertos.
- Exige el `.htaccess` activo: lo protegen el raíz y `cfw/datos/.htaccess`. Sin ellos se podría descargar. Con varios servidores, todos deben compartir la misma clave.
- **Otra ubicación**, por ejemplo fuera de la raíz web: añade a `cfw/config.ini` la opción `cifrado.clave_privada` con la ruta absoluta del archivo, en su propia sección:
  ```
  [cifrado]
  cifrado.clave_privada = "/srv/cfw-claves/clave_privada.pem"
  ```
  - Es opcional: si la línea no está, se usa `cfw/datos/clave_privada.pem`. Si está, debe ser una ruta absoluta (`/…`; en Windows, `C:\…` o `C:/…`); se ignoran los espacios del principio y del final. Un valor vacío, relativo o que `parse_ini_file` convierte en vacío (`off`, `no`, `false`, `none`) no se acepta: los formularios muestran el aviso y el log una línea `CFW:`. Para volver a la ruta por defecto, borra o comenta la línea.
  - PHP debe poder leer el archivo (también con `open_basedir`, si lo usas). Se aplican los mismos permisos exigidos de arriba. Para que se genere solo, Apache tiene que poder escribir en la carpeta. Si no, genérala a mano con la orden de arriba y esa ruta; así la carpeta puede quedar sin escritura para Apache, que es lo más seguro. No uses una carpeta compartida ni una dentro de la raíz web.

**Migración desde md5.** Los md5 existentes se aceptan una vez y se convierten a `password_hash` en el primer login correcto, sin que nadie cambie su contraseña. Las cuentas que nunca inicien sesión siguen en md5. El hash también se renueva si cambia el coste de `PASSWORD_DEFAULT`, por ejemplo al cambiar de versión de PHP.

**Límite:** las contraseñas nuevas no pueden pasar de 72 bytes ni tener caracteres nulos (bcrypt ignora lo que sobra y `password_hash` falla con NUL).

**Para sitios existentes:**
- `pass_valida` y `pass_hash` son funciones globales nuevas: no pueden repetirse en `cfw/specific/utils/functions.php`.
- `comprobarUserPass` recibe la contraseña real, no su md5.
- `AdminHelper::editfield` ya no admite el campo `pass`.
- El formulario de datos de `cuenta` pasa a `web/cuenta_usuario.tpl`, con `data-cifrar` y sin `name` en las contraseñas. `web/cuenta.tpl` lo pinta con `{aviso_cifrado}` y `{form_usuario}`. Si el sitio tiene su propia copia de `web/cuenta.tpl` en `cfw/specific/templates/`, actualízala: con la antigua no se ve el formulario nuevo, o las contraseñas se envían en claro y el servidor responde «formulario caducado».
- node-forge 1.4.2 (`js/forge-1.4.2.min.js`): copia sin modificar de `dist/forge.min.js` del paquete npm `node-forge` 1.4.2 (repositorio `digitalbazaar/forge`), sha256 `52b553e747df79daa5056ff5e4dff37094c7d8a71194efe2b153d7c3f12763ff`. Licencia BSD-3-Clause o GPL-2.0, a elegir.

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
- Los `.ini` de idioma se fusionan por clave: `general` y `specific` de `lang_default`, y encima los del idioma actual. Un `specific/lang/<default>` ya no gana a `general/lang/<idioma>` entero, solo aporta sus claves. `text_error_usuario_mail` pasa a `textos.ini`.
- `get_array_text` y `cfw/general/lang/es/menu.php` ya no se usan, aunque el fichero sigue en el árbol. Ya no se carga PHP desde `lang/`: un `menu.php` propio deja de aplicarse. El menú usa `{text_menu_inicio}` y `{text_menu_login}`.
- `cfw/specific/lang/{es,en}/main.ini` siguen en el árbol y tienen prioridad sobre `general` en su idioma: cambiar `titulo` o `mensaje` en `cfw/general/lang/<código>/main.ini` no tiene efecto mientras existan esas claves en `specific`.
- Los `.ini` de idioma se leen con `INI_SCANNER_RAW`. Un fichero con una comilla sin cerrar o una línea sin clave se ignora y deja una línea `CFW:` en el log.
- `langs` de `config.ini` queda sin uso.
- `t()` es global: una función `t` en `cfw/specific/utils/functions.php` da un fatal.
- `titulo` y `singular` de las subclases de `AdminHelper` no se traducen solos: los define cada sitio.
- PHP 8.2 da `Deprecated` con las propiedades dinámicas: las subclases de `cfw/specific/` deben declarar las suyas, con una visibilidad compatible con la del padre. `private` da un fatal, y `protected` también si el padre la declara `public`, como `$tabla` en `Orm` o `$vars` en `Page`.
- Se eliminan la función global `saveImage` y `cfw/general/utils/images.php` (`saveImageProductos`). Usa `AdminHelper::saveImage`.
- `index.php` solo despacha clases que hereden de `Page`. Las demás dan 404, también las de `cfw/specific/pages/`.
- `main` solo pinta las plantillas de `$a_plantillas_permitidas`. Una subclase de `cfw/specific/` que use otras debe ampliar esa propiedad.
- La portada usa `portada.tpl` y `portada.ini`: `titulo` y `mensaje` de `main.ini` ya no se muestran en ella, y `/admin/main/init/main` da 404. `main.tpl` sigue en uso para `AdminHelper::delete`. Para personalizar la portada, usa `cfw/specific/lang/<código>/portada.ini` o `cfw/specific/templates/portada.tpl`. Una subclase de `main` con su propia lista de plantillas debe incluir `portada`. Un sitio derivado debe sustituir esta portada en producción, porque publica el framework y sus versiones.
- En `AdminHelper::form`, una subida fallida deshace el alta con un `DELETE` físico (`Orm::eliminarFila`), y `guardarSubidas` devuelve `bool`.
- El login usa jQuery 4.0.0 (`js/jquery-4.0.0.min.js`); los formularios con contraseña, node-forge 1.4.2 (`js/forge-1.4.2.min.js`) y `js/cifrado.js`; y el listado de `AdminHelper`, Prototype 1.7.3 (`js/prototype-1.7.3.js`) con TableKit 1.3b. Prototype 1.7.3 lleva el aviso CVE-2020-27511 (ReDoS en `stripTags` y `unescapeHTML`), sin parche: no pases datos de usuario a esas funciones.
- jQuery y Prototype no deben cargarse en la misma página.
- Se retira jHtmlArea: desaparecen `$jhtmlarea` de `AdminHelper` y la plantilla `elements_html`.
- `main.js` solo conserva `sendLoginForm` y `devolver_mensaje`. Se eliminan `sendRegisterForm`, `comprobar_mail`, `sendAForm`, `muestraError`, `salir`, `sendFormUser` y los textos que usaban.
- El login ya no envía el md5 de la contraseña, sino la contraseña cifrada (ver «Contraseñas»). `js/js-md5-0.9.2.min.js` ya no se carga.
- Si activas la edición en línea de TableKit, configura `TableKit.options.editAjaxURI` y añade el `csrf` al POST: hoy no lo envía y la respuesta sería 403.
