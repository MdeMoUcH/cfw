# Instalación y despliegue
Qué necesita CFW y cómo ponerlo en marcha. Para saber qué es y cómo funciona, ver [README.md](README.md).

## Requisitos
- PHP 8.2 o superior (probado en 8.2, 8.3, 8.4 y 8.5), Apache con `mod_rewrite` y `.htaccess` activo, MySQL 5.7 y las extensiones `mysqli` y `openssl`.
- La extensión `posix` se recomienda: sin ella no se comprueba el propietario de la clave privada (ver «Clave privada del cifrado»).
- La extensión GD (con JPEG y PNG) solo hace falta para subir imágenes. Sin ella, `saveImage` devuelve `false`.
- La extensión `mbstring` solo hace falta para `filtro_mysql_utf8`.

## Puesta en marcha
1. Carga `db.sql`. No crea ningún usuario.
2. Crea el primer administrador con el `INSERT` de ejemplo comentado en `db.sql`, con tu email y el hash de tu contraseña generado con `password_hash`.
3. Revisa `cfw/config.ini`. El archivo está versionado: pon los datos reales solo en el servidor y no los subas al repositorio. `desarrollo = true` muestra errores y detalles; no lo actives en un servidor público.
4. Usa siempre HTTPS. El framework no lo fuerza, y el cifrado de las contraseñas no lo sustituye.
5. Comprueba que Apache aplica los `.htaccess` (ver «Protección de `cfw/` con `.htaccess`»).

## Protección de `cfw/` con `.htaccess`
Lo único que impide descargar `cfw/` son el `.htaccess` raíz y `cfw/data/.htaccess`. Ahí están la clave privada del cifrado, `cfw/config.ini` con los datos de la base de datos y `cfw/data/idiomas.ini`. El `.htaccess` raíz también bloquea los `.sql`, `.md`, `.ini` y `.tpl` y los archivos ocultos, como `.git/`. **Si el servidor no lee los `.htaccess`, no da ningún error: sirve esos archivos tal cual.**
- **Apache con `AllowOverride None`.** Es el valor por defecto de Apache desde la 2.3.9, y el que trae Debian/Ubuntu para `/var/www/` en `apache2.conf`. Pon `AllowOverride All` en el `<Directory>` del sitio (o en su `VirtualHost`) y reinicia Apache. La imagen oficial de Docker `php:*-apache` ya lo hace en `docker-php.conf`; un Apache instalado con `apt` no.
- **nginx, Caddy, LiteSpeed sin soporte de `.htaccess` u otro servidor.** Ignoran los `.htaccess`. CFW no está preparado para ellos: habría que traducir a su configuración todas las reglas del `.htaccess` raíz, incluidos los bloqueos.
- **Comprobación.** Hazla tras instalar y tras cada cambio de servidor o de configuración. Las tres URL deben dar `403`:
  ```
  curl -s -o /dev/null -w '%{http_code}\n' https://tu-sitio/cfw/data/clave_privada.pem
  curl -s -o /dev/null -w '%{http_code}\n' https://tu-sitio/cfw/config.ini
  curl -s -o /dev/null -w '%{http_code}\n' https://tu-sitio/db.sql
  ```
  Un `200` significa que el archivo se descarga: corrige el servidor y, si era la clave privada, rótala (ver «Clave privada del cifrado»). Si era `config.ini`, cambia también la contraseña de la base de datos. Otra pista: si los `.htaccess` no se aplican, `/login` y `/admin` dan 404.
- Aun con los `.htaccess` activos, lo más seguro es tener la clave privada fuera de la raíz web (ver «Otra ubicación» en «Clave privada del cifrado»).

## Carpeta `cfw/data` y permisos
Apache escribe en `cfw/data/` la clave privada del cifrado, si no se configura otra ubicación, y la opción de multiidioma, `cfw/data/idiomas.ini` (ver «Idiomas» en [README.md](README.md)).

Antes de usarla, da a Apache permiso sobre la carpeta. La aplicación crea el fichero la primera vez que guardas:
```
chgrp www-data cfw/data && chmod 770 cfw/data
```
- Repite esta orden tras un clon nuevo, porque git no conserva el grupo ni el modo. `chgrp` puede requerir privilegios.
- Apache no debe ser propietario de `cfw/general/lang/` ni tener escritura sobre ella, tampoco de grupo o recursiva. Los textos se pintan sin escapar: quien pueda escribir ahí puede inyectar código en todo el sitio, incluida la administración.

## Clave privada del cifrado
`cfw/data/clave_privada.pem` por defecto, RSA de 3072 bits.
- Se genera sola en el primer uso, con permisos 0600, si Apache puede escribir en `cfw/data/` (ver «Carpeta `cfw/data` y permisos»). Si no puede, o la clave no es válida, se muestra un aviso en lugar de los formularios con contraseña (en `users` solo se puede editar sin cambiarla) y el log una línea `CFW:`; un archivo no válido no se sobrescribe.
- También se puede generar a mano, como el usuario de Apache: `sudo -u www-data openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -out cfw/data/clave_privada.pem`.
- **Permisos exigidos.** Si no se cumplen, la clave se rechaza sin sobrescribirla (aviso en los formularios y una línea `CFW:` en el log):
  - El archivo debe ser un archivo normal, no un enlace, cuyo propietario sea el usuario con el que corre PHP (`www-data`, o el de la cuenta con PHP-FPM o suEXEC). Ni el grupo ni otros pueden tener escritura sobre él: 0600 o 0400. Para corregirlo: `chown www-data cfw/data/clave_privada.pem && chmod 600 cfw/data/clave_privada.pem`.
  - La carpeta no puede tener escritura para todos (por ejemplo 777 o `/tmp`). Se admite la de grupo, como el 775 de un clon o el 770 con el grupo de Apache (ver «Carpeta `cfw/data` y permisos»), y cualquier propietario, también root. Para corregirlo: `chmod o-w cfw/data`.
  - El propietario solo se comprueba con la extensión `posix`, que se recomienda tener. Sin ella la garantía es más débil: se acepta una clave de cualquier propietario si cumple el resto (archivo normal, sin escritura de grupo ni de otros, carpeta sin escritura para todos), y al generarla queda una línea `CFW:` en el log que lo avisa. En Windows no se comprueban propietario ni permisos, solo que no sea un enlace.
- Para rotarla, bórrala: se crea otra en la siguiente petición. Solo fallan una vez los formularios que estaban abiertos.
- En `cfw/data/` solo la protegen el `.htaccess` raíz y `cfw/data/.htaccess`. Si el servidor no los aplica (nginx o `AllowOverride None`), se puede descargar sin que se avise de nada: ver «Protección de `cfw/` con `.htaccess`». Con varios servidores, todos deben compartir la misma clave.
- **Otra ubicación**, por ejemplo fuera de la raíz web: añade a `cfw/config.ini` la opción `cifrado.clave_privada` con la ruta absoluta del archivo, en su propia sección:
  ```
  [cifrado]
  cifrado.clave_privada = "/srv/cfw-claves/clave_privada.pem"
  ```
  - Es opcional: si la línea no está, se usa `cfw/data/clave_privada.pem`. Si está, debe ser una ruta absoluta (`/…`; en Windows, `C:\…` o `C:/…`); se ignoran los espacios del principio y del final. Un valor vacío, relativo o que `parse_ini_file` convierte en vacío (`off`, `no`, `false`, `none`) no se acepta: los formularios muestran el aviso y el log una línea `CFW:`. Para volver a la ruta por defecto, borra o comenta la línea.
  - PHP debe poder leer el archivo (también con `open_basedir`, si lo usas). Se aplican los mismos permisos exigidos de arriba. Para que se genere solo, Apache tiene que poder escribir en la carpeta. Si no, genérala a mano con la orden de arriba y esa ruta; así la carpeta puede quedar sin escritura para Apache, que es lo más seguro. No uses una carpeta compartida ni una dentro de la raíz web.

## Despliegue
- **Multiidioma** (`cfw/data/idiomas.ini`):
  - Conserva el fichero entre despliegues. Un `rsync --delete` o un `git clean -X` lo borran: el multiidioma se desactiva y `/admin/idiomas` muestra un aviso.
  - La opción está pensada para un solo servidor: con varios, el fichero no se comparte entre ellos.
- **Clave privada:** con varios servidores, todos deben compartir la misma clave (ver «Clave privada del cifrado»).
- **Límite de intentos** (ver «Seguridad» en [README.md](README.md)): los contadores son archivos en `sys_get_temp_dir()/cfw_intentos/`.
  - Con varios servidores, cada uno cuenta por su lado.
  - `get_ip` usa solo `REMOTE_ADDR`. Detrás de un proxy o balanceador, todos los visitantes comparten esa IP, así que cualquiera puede bloquear el login de un email durante 15 minutos desde el último fallo.
  - Vaciar esa carpeta, o un reinicio que limpie el temporal del sistema, pone los contadores a cero. Otras aplicaciones que corran con el mismo usuario de PHP también pueden borrarlos o modificarlos, es decir, poner a cero el límite o provocar bloqueos.
  - Si `sys_get_temp_dir()` es un `/tmp` compartido entre los usuarios del servidor, otro usuario local podría crear antes `cfw_intentos/` con escritura para todos. CFW solo comprueba que la carpeta exista, no su propietario ni sus permisos, y la usaría igual. Ese usuario podría borrar o modificar los contadores, y también dejar en ella enlaces simbólicos con el nombre de un contador, que PHP seguiría al escribirlo: el archivo de destino quedaría reemplazado por un número si el usuario de PHP puede escribir en él. También podría dejarla sin escritura para PHP: el límite quedaría desactivado sin otra señal que la línea `CFW: no se pudo escribir` en el log de errores. Exige una cuenta local en el servidor. Conviene un temporal propio del usuario de PHP, con `sys_temp_dir` en `php.ini` (tiene prioridad) o con la variable de entorno `TMPDIR` del proceso de PHP, que es de donde lee `sys_get_temp_dir()`, o `PrivateTmp` de systemd.

Para actualizar un sitio que ya usa CFW, ver [MIGRACION.md](MIGRACION.md).
