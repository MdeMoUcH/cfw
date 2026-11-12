# Instalación y despliegue
Qué necesita CFW, cómo crear un sitio con él, ponerlo en marcha y actualizarlo. Para saber qué es y cómo funciona, ver [README.md](README.md).

## Requisitos
- PHP 8.2 o superior (probado en 8.2, 8.3, 8.4 y 8.5), Apache 2.4.10 o superior con `mod_rewrite`, `mod_headers` (`a2enmod headers`) y `.htaccess` activo, MySQL 5.7 y las extensiones `mysqli` y `openssl`.
- `mod_headers` sirve para que el `.htaccess` raíz añada las cabeceras de seguridad (`X-Frame-Options`, `Content-Security-Policy: frame-ancestors 'none'`, `X-Content-Type-Options` y `Referrer-Policy`) a lo que no pasa por PHP: estáticos, redirecciones y errores de Apache. Sin el módulo no se avisa de nada: esas respuestas salen sin ellas, y las páginas siguen protegidas porque las envía `index.php`.
- La extensión `posix` se recomienda: sin ella no se comprueba el propietario de la clave privada (ver «Clave privada del cifrado»).
- La extensión GD (con JPEG y PNG) solo hace falta para subir imágenes. Sin ella, `saveImage` devuelve `false`.
- La extensión `mbstring` solo hace falta para `filtro_mysql_utf8`.

## Crear un sitio
Cada sitio es un clon de CFW en una versión, es decir, en su etiqueta de git (ver «Numeración» en [MIGRACION.md](MIGRACION.md)). El repositorio de CFW queda como remoto `cfw` y el del sitio como `origin`, y así el sitio se actualiza con git (ver «Actualizar un sitio»):
```
git clone --branch 2.0.0 https://github.com/MdeMoUcH/cfw.git mi-sitio
cd mi-sitio
git switch -c main
git remote rename origin cfw
git remote add origin <url del repositorio del sitio>
git push -u origin main
```
- `git switch -c main` hace falta porque clonar en una etiqueta deja el HEAD desacoplado: sin una rama, los commits del sitio no quedan en ninguna.
- El repositorio del sitio debe estar vacío, sin README ni licencia iniciales, o el `push` falla.
- Lo propio del sitio va en `cfw/specific/` y en los archivos que el sitio adapta fuera de ella (ver «Qué se puede redefinir en `specific`» y «Qué queda fuera de `specific`» en [README.md](README.md)). No cambies `cfw/general/`: cada cambio ahí choca con los de CFW al actualizar.
- No cambies `cfw/config.ini`, que trae los valores por defecto de CFW. Lo del sitio va en `cfw/specific/config.ini`, que se versiona, y lo de cada servidor, con las contraseñas, en `cfw/data/config.ini`, que no se versiona (ver «Puesta en marcha»).
- Después, sigue con «Puesta en marcha».

## Puesta en marcha
1. Carga `db.sql`. No crea ningún usuario.
2. Crea el primer administrador con el `INSERT` de ejemplo comentado en `db.sql`, con tu email y el hash de tu contraseña generado con `password_hash`.
3. Configura el sitio. CFW lee `cfw/config.ini` (sus valores por defecto, que no se tocan), después `cfw/specific/config.ini` y por último `cfw/data/config.ini`, y cada uno pisa al anterior clave a clave. Los dos últimos son opcionales, y las secciones (`[general]`...) solo sirven para ordenar.
   - **`cfw/data/config.ini`**, en cada servidor y sin versionar: los datos de la base de datos (`db.*`), `urlbase` (la URL del sitio), `desarrollo` y `analytics`, y `pathbase` (la ruta absoluta de la raíz del sitio) solo si no es la carpeta donde está instalado, que es su valor por defecto. Si a `urlbase` o `pathbase` les falta la barra final, se les añade. `desarrollo = true` muestra errores y detalles; no lo actives en un servidor público. Aquí van también `intentos.carpeta` y `cifrado.clave_privada`, si los usas, y normalmente las rutas de subida de `AdminHelper` (ver «Carpeta `cfw/data` y permisos»). Por ejemplo:
     ```
     [entorno]
     desarrollo = false

     [general]
     urlbase = https://tu-sitio/

     [base_de_datos]
     db.host = localhost
     db.user = usuario
     db.pass = "contraseña"
     db.name = basededatos
     ```
   - **`cfw/specific/config.ini`**, versionado con el sitio: `name`, `description`, `keywords`, `lang_default`, `class.default`, `email` y las claves propias del sitio. Nada secreto.
   - Un fichero mal formado se ignora entero, como si no existiera, y deja una línea `CFW:` en el log con su ruta, sin su contenido. Si se ignora `cfw/data/config.ini`, el sitio se queda sin base de datos.
   - **Permisos:** Apache tiene que poder leerlos. Lo recomendable es que `cfw/data/config.ini` sea del usuario que despliega, con grupo `www-data` y modo 640 (`chgrp www-data cfw/data/config.ini && chmod 640 cfw/data/config.ini`), y que la carpeta tenga el bit sticky (ver «Carpeta `cfw/data` y permisos»).
   - **Valores con comillas o barras invertidas**, como muchas contraseñas: escríbelos entre comillas dobles, con `\"` por cada `"`, `\\` por cada `\` y `\${` en lugar de `${`, que si no se toma como una variable de entorno y desaparece sin error. Los demás caracteres (`;`, `!`, `=`, `(`, `'`, `$`...) salen tal cual entre comillas dobles. Por ejemplo, la contraseña `p"a\b${c` se escribe `db.pass = "p\"a\\b\${c"`. No uses comillas simples: no admiten `'`, y una `\` antes de la comilla hace que se pierdan sin error las claves que van detrás.
   - **Comprueba que se ha leído bien**, sin mostrar los valores, desde la raíz del sitio y como Apache (ver «Órdenes como Apache» en «Carpeta `cfw/data` y permisos»):
     ```
     sudo -u www-data php -r '$c = @parse_ini_file("cfw/data/config.ini"); if (!is_array($c)) exit("no válido\n"); foreach ($c as $k => $v) echo $k, " ", is_array($v) ? "array" : strlen($v), "\n";'
     ```
     Debe listar todas las claves, cada una con la longitud que esperas para su valor (en bytes: una letra con tilde cuenta 2). Una clave que falte indica una línea mal escrita antes de ella, y una longitud distinta, una comilla o una barra mal escapadas. Haz lo mismo con `cfw/specific/config.ini`.
4. Usa siempre HTTPS. El framework no lo fuerza, y el cifrado de las contraseñas no lo sustituye.
5. Comprueba que Apache aplica los `.htaccess` (ver «Protección de `cfw/` con `.htaccess`»).

## Protección de `cfw/` con `.htaccess`
Lo único que impide descargar `cfw/` son el `.htaccess` raíz y `cfw/data/.htaccess`. Ahí están la clave privada del cifrado, `cfw/data/config.ini` con los datos de la base de datos y `cfw/data/idiomas.ini`. El `.htaccess` raíz también bloquea los `.sql`, `.md`, `.ini` y `.tpl` y los archivos ocultos, como `.git/`. **Si el servidor no lee los `.htaccess`, no da ningún error: sirve esos archivos tal cual.**
- **Apache con `AllowOverride None`.** Es el valor por defecto de Apache desde la 2.3.9, y el que trae Debian/Ubuntu para `/var/www/` en `apache2.conf`. Pon `AllowOverride All` en el `<Directory>` del sitio (o en su `VirtualHost`) y reinicia Apache. La imagen oficial de Docker `php:*-apache` ya lo hace en `docker-php.conf`; un Apache instalado con `apt` no.
- **nginx, Caddy, LiteSpeed sin soporte de `.htaccess` u otro servidor.** Ignoran los `.htaccess`. CFW no está preparado para ellos: habría que traducir a su configuración todas las reglas del `.htaccess` raíz, incluidos los bloqueos.
- **Comprobación.** Hazla tras instalar y tras cada cambio de servidor o de configuración. Las tres URL deben dar `403`:
  ```
  curl -s -o /dev/null -w '%{http_code}\n' https://tu-sitio/cfw/data/clave_privada.pem
  curl -s -o /dev/null -w '%{http_code}\n' https://tu-sitio/cfw/data/config.ini
  curl -s -o /dev/null -w '%{http_code}\n' https://tu-sitio/db.sql
  ```
  Un `200` significa que el archivo se descarga: corrige el servidor y, si era la clave privada, rótala (ver «Clave privada del cifrado»). Si era `cfw/data/config.ini`, cambia también la contraseña de la base de datos. Otra pista: si los `.htaccess` no se aplican, `/login` y `/admin` dan 404.
- **Comprobación de las cabeceras de seguridad.** Debe mostrar `X-Frame-Options: DENY` una sola vez. Si no sale, falta `mod_headers` (ver «Requisitos»):
  ```
  curl -sI https://tu-sitio/css/style.css | grep -i x-frame-options
  ```
- Aun con los `.htaccess` activos, lo más seguro es tener la clave privada fuera de la raíz web (ver «Otra ubicación» en «Clave privada del cifrado»).

## Carpeta `cfw/data` y permisos
Apache escribe en `cfw/data/` la clave privada del cifrado y los contadores del límite de intentos (`cfw/data/intentos/`), si no se configura otra ubicación, y la opción de multiidioma, `cfw/data/idiomas.ini` (ver «Idiomas» en [README.md](README.md)). Ahí está también `cfw/data/config.ini`, la configuración del servidor, que escribes tú (ver «Puesta en marcha»). Apache no lo modifica, pero como escribe en la carpeta podría borrarlo o sustituirlo, aunque sea 640 y de otro usuario: lo impide el bit sticky de la orden de abajo.

Antes de usarla, da a Apache permiso sobre la carpeta. La aplicación crea el fichero la primera vez que guardas. Esta orden, como todas las de este documento con rutas relativas, se ejecuta desde la raíz del sitio:
```
chgrp www-data cfw/data && chmod 1770 cfw/data
```
- Repite esta orden tras un clon nuevo, porque git no conserva el grupo ni el modo. `chgrp` puede requerir privilegios.
- **El bit sticky (el `1` de `1770`)** impide que Apache borre o renombre en `cfw/data` lo que no es suyo. Sin él, un fallo que le dejara escribir en `cfw/data` con un nombre elegido le bastaría para cambiar `cfw/data/config.ini` y, con ello, `pathbase` (qué PHP se incluye), `desarrollo`, `cifrado.clave_privada` o `intentos.carpeta`. Para que proteja:
  - `cfw/data/config.ini` tiene que existir y ser del usuario que despliega: si falta, Apache puede crearlo. Créalo aunque esté vacío.
  - La carpeta `cfw/data` no puede ser de Apache.
  - Lo que crea Apache en ella (`clave_privada.pem`, `idiomas.ini` e `intentos/`) tiene que ser suyo, como queda al crearlo él; si no, no podrá sustituirlo ni borrarlo.
  - Para borrar o apartar como Apache algo de `cfw/data` que no es suyo (por ejemplo, una clave privada o una carpeta `intentos` de otro usuario), quita el bit a la carpeta mientras dura la operación: `chmod -t cfw/data` antes y `chmod +t cfw/data` después, con `sudo` si no eres su propietario.
- Apache no debe ser propietario ni tener escritura, tampoco de grupo, sobre la raíz del sitio, `cfw/`, `js/` ni el resto de archivos y carpetas, salvo `cfw/data`, las carpetas de subida y las rutas propias de `intentos.carpeta` y `cifrado.clave_privada` dentro de `cfw/` (ver abajo). Ahí están el código, los textos (que se pintan sin escapar) y `js/cifrado.js`, que cifra las contraseñas en el navegador: quien pueda escribir en ellos puede inyectar código en todo el sitio, incluida la administración, o capturar las contraseñas.
  - Las carpetas de subida son las que una subclase de `AdminHelper` toma de la configuración (normalmente de `cfw/data/config.ini`): `<config_name>.path` para `saveFile` y `<config_name>.img.path` para `saveImage`, con barra final, que aquí no se añade sola, a diferencia de `urlbase` y `pathbase`. Apache tiene que poder escribir en ellas. Deben ser carpetas solo para subidas, sin código ni otros archivos del sitio: no pueden ser la raíz, `css/`, `img/` ni `js/`, ni estar en `cfw/` o dentro de ella (incluidas `cfw/general` y `cfw/specific`). El CFW base no configura ninguna.
  - Para comprobarlo, ejecuta esta orden desde la raíz del sitio. No debe mostrar nada, salvo las carpetas de subida y su contenido, y las rutas de `intentos.carpeta` o `cifrado.clave_privada` si las pones dentro de `cfw/` pero fuera de `cfw/data`, que la regla de `cfw/` del `.htaccess` raíz también bloquea (los paréntesis hacen falta para que `-print` cubra las dos condiciones, y el `-print` explícito, para que no salga `cfw/data`):
    ```
    sudo -u www-data find . -path ./cfw/data -prune -o \( -writable -o -user www-data \) -print
    ```
    En esas rutas también salen los contadores de intentos de la carpeta y, si Apache puede escribir en ella o es su propietario, la carpeta que las contiene. Esa carpeta tiene que ser propia, solo para esas rutas, nunca `cfw/`, `cfw/general` ni `cfw/specific`, aunque después le quites la escritura: la carpeta de intentos y la clave las crea siempre Apache, también a mano, así que tiene que poder escribir en ella al menos mientras tanto (ver «Creación» en «Despliegue» y «Clave privada del cifrado»). Si sale una clave privada fuera de `cfw/`, hay que moverla y rotarla (ver «Otra ubicación» y «Para rotarla» en «Clave privada del cifrado»).
    Un «Permission denied» (sale por stderr) indica una carpeta que Apache no puede leer y que, por tanto, no ha quedado comprobada.
- **Órdenes como Apache.** Lanza las órdenes `sudo -u www-data` de este documento con un `sudo` que use `use_pty`. Sin él, la orden comparte tu terminal: un `www-data` comprometido podría engancharse a la orden con `ptrace`, porque corre con su mismo usuario, o meter órdenes en tu terminal con `TIOCSTI`, que tu shell ejecutaría al terminar con tus permisos (los de root, si la lanzas desde una terminal de root). Con `Defaults use_pty`, sudo le da una pseudoterminal propia; es el valor por defecto desde sudo 1.9.14 y viene en los sudoers de las versiones actuales de Debian y Ubuntu. Para comprobarlo, en esa misma terminal, `tty` y `sudo -u www-data tty` deben mostrar terminales distintas. Si muestran la misma, añade `Defaults use_pty` con `visudo`.

## Clave privada del cifrado
`cfw/data/clave_privada.pem` por defecto, RSA de 3072 bits.
- Se genera sola en el primer uso, con permisos 0600, si Apache puede escribir en `cfw/data/` (ver «Carpeta `cfw/data` y permisos»). Si no puede, o la clave no es válida, se muestra un aviso en lugar de los formularios con contraseña (en `users` solo se puede editar sin cambiarla) y el log una línea `CFW:`; un archivo no válido no se sobrescribe.
- También se puede generar a mano, siempre como Apache. Así la clave pasa de `openssl` directamente a su archivo, que queda de `www-data` y en 0600, sin copias en temporales ni en el historial:
  ```
  sudo -u www-data sh -c 'umask 077 && openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -out cfw/data/clave_privada.pem'
  ```
  - Apache tiene que poder escribir en la carpeta. Si no puede (por ejemplo, con `cfw/data` en 550), dale la escritura solo mientras la borras y la generas, y quítasela después. Con la carpeta del grupo `www-data`, como en «Carpeta `cfw/data` y permisos», es `chmod g+w cfw/data` antes y `chmod g-w cfw/data` después, con `sudo` si no eres su propietario; si no es de ese grupo (por ejemplo, tras un clon nuevo), pásala antes con `chgrp www-data cfw/data`. Si el propietario es Apache, `u+w` y `u-w`.
  - Si ya hay algo con ese nombre (por ejemplo, una clave no válida o una carpeta), bórralo antes, también como Apache: `sudo -u www-data rm -r cfw/data/clave_privada.pem`. Si no es de Apache, quita antes el bit sticky de `cfw/data` (ver «Carpeta `cfw/data` y permisos»).
  - No ejecutes como root ninguna orden dentro de la carpeta de la clave ni sobre la clave, tampoco con la escritura quitada: root seguiría un enlace que `www-data` hubiera puesto con ese nombre y acabaría sobrescribiendo otro archivo del sistema o dándoselo a `www-data`. Las órdenes sobre la propia carpeta, como las de arriba, son seguras, porque Apache no puede escribir en `cfw/`.
  - Comprueba que el archivo existe antes de seguir: algunas versiones de `openssl` terminan sin error aunque no hayan podido escribirlo. Se acepta una clave RSA de 2048 bits o más.
- **Permisos exigidos.** Si no se cumplen, la clave se rechaza sin sobrescribirla (aviso en los formularios y una línea `CFW:` en el log):
  - El archivo debe ser un archivo normal, no un enlace, cuyo propietario sea el usuario con el que corre PHP (`www-data`, o el de la cuenta con PHP-FPM o suEXEC; solo se comprueba con `posix`, ver abajo). Ni el grupo ni otros pueden tener escritura sobre él, y otros tampoco lectura: 0600, 0640, 0400 o 0440. Para corregirlo, bórrala como Apache (`sudo -u www-data rm -r cfw/data/clave_privada.pem`; en `cfw/data`, quitando antes el bit sticky de la carpeta, ver «Carpeta `cfw/data` y permisos»), y genérala de nuevo, con la escritura temporal si hace falta (ver arriba).
  - La carpeta no puede tener escritura para todos (por ejemplo 777 o `/tmp`). Se admite la de grupo, como el 775 de un clon o el 1770 con el grupo de Apache (ver «Carpeta `cfw/data` y permisos»), y cualquier propietario, también root. Para corregirlo: `chmod o-w cfw/data`. La carpeta de los contadores de intentos sigue la misma regla para la carpeta que la contiene (ver «Despliegue»).
  - El propietario solo se comprueba con la extensión `posix`, que se recomienda tener. Sin ella la garantía es más débil: se acepta una clave de cualquier propietario si cumple el resto (archivo normal, sin escritura de grupo ni de otros ni lectura de otros, carpeta sin escritura para todos), y al generarla queda una línea `CFW:` en el log que lo avisa. En Windows no se comprueban propietario ni permisos, solo que no sea un enlace.
- Para rotarla, bórrala como Apache. Si Apache puede escribir en la carpeta, se crea otra en la siguiente petición; si no, dale la escritura temporal para borrarla y generar otra a mano (ver arriba). Solo fallan una vez los formularios que estaban abiertos. Rotarla también levanta los bloqueos en curso del límite de intentos, porque los nombres de los contadores dependen de ella (ver «Límite de intentos» en «Despliegue»).
- En `cfw/data/` solo la protegen el `.htaccess` raíz y `cfw/data/.htaccess`. Si el servidor no los aplica (nginx o `AllowOverride None`), se puede descargar sin que se avise de nada: ver «Protección de `cfw/` con `.htaccess`». Con varios servidores, todos deben compartir la misma clave.
- **Otra ubicación**, por ejemplo fuera de la raíz web: añade a `cfw/data/config.ini` la opción `cifrado.clave_privada` con la ruta absoluta del archivo, en su propia sección:
  ```
  [cifrado]
  cifrado.clave_privada = "/srv/cfw-claves/clave_privada.pem"
  ```
  - Es opcional: si la línea no está, se usa `cfw/data/clave_privada.pem`. Si está, debe ser una ruta absoluta (`/…`; en Windows, `C:\…` o `C:/…`); se ignoran los espacios del principio y del final. Un valor vacío, relativo o que `parse_ini_file` convierte en vacío (`off`, `no`, `false`, `none`) no se acepta: los formularios muestran el aviso y el log una línea `CFW:`. Para volver a la ruta por defecto, borra o comenta la línea.
  - PHP debe poder leer el archivo (también con `open_basedir`, si lo usas). Se aplican los mismos permisos exigidos de arriba. Para que se genere solo, Apache tiene que poder escribir en la carpeta. Si no, genérala a mano como se explica arriba, con esa ruta y dando la escritura temporal en su carpeta, no en `cfw/data` (si no es del grupo `www-data`, pásala antes a ese grupo, por ejemplo con `sudo chgrp www-data /srv/cfw-claves`). Después la carpeta puede quedar sin escritura para Apache, que es lo más seguro. Apache tampoco debe poder escribir en la carpeta que la contiene ni ser su propietario, porque podría cambiarla por un enlace. No uses una carpeta compartida ni una dentro de la raíz web y fuera de `cfw/`.

## Despliegue
- **Configuración del servidor** (`cfw/data/config.ini`): conserva el fichero entre despliegues. Un `rsync --delete` o un `git clean -X` lo borran, y el sitio se queda sin base de datos.
- **Multiidioma** (`cfw/data/idiomas.ini`):
  - Conserva el fichero entre despliegues. Un `rsync --delete` o un `git clean -X` lo borran: el multiidioma se desactiva y `/admin/idiomas` muestra un aviso.
  - La opción está pensada para un solo servidor: con varios, el fichero no se comparte entre ellos.
  - Tiene que ser un archivo normal. Un enlace (también roto), un FIFO o una carpeta en su lugar desactivan el multiidioma: `/admin/idiomas` muestra el aviso y queda una línea `CFW:` en el log en cada petición. Guardar esa página lo sustituye por un archivo normal, salvo si es una carpeta o, con el bit sticky en `cfw/data`, si no es de Apache: entonces el guardado falla. Bórralo a mano como Apache, quitando antes el bit si no es suyo (ver «Carpeta `cfw/data` y permisos»).
  - Se crea con modo 0640, salvo que una ACL por defecto de `cfw/data` dé otro sin permisos para otros (por ejemplo, 0660). Si la ACL da permisos para otros, o quita al propietario la lectura o la escritura, el guardado falla con el mensaje de error y una línea `CFW:` en el log.
- **Clave privada:** con varios servidores, todos deben compartir la misma clave (ver «Clave privada del cifrado»).
- **Límite de intentos** (ver «Seguridad» en [README.md](README.md)): los contadores son archivos en `cfw/data/intentos/`, si no se configura otra carpeta.
  - **Otra carpeta**, por ejemplo fuera de la raíz web: añade a `cfw/data/config.ini` la opción `intentos.carpeta`, en su propia sección:
    ```
    [intentos]
    intentos.carpeta = "/srv/cfw-intentos/intentos"
    ```
    Las reglas de la ruta son las de `cifrado.clave_privada`: absoluta (`/…`; en Windows, `C:\…` o `C:/…`) y sin los espacios del principio y del final; además se quitan las barras finales. A diferencia de la clave, un valor no válido (vacío, `off`, `no`, `false`, `none`, relativo o `/`) no desactiva nada: se usa `cfw/data/intentos` y queda una línea `CFW:` en el log en cada intento de login o de guardado en `cuenta` con la contraseña actual.
    La carpeta que la contiene (en el ejemplo, `/srv/cfw-intentos`) tiene que ser propia, como la de la clave en «Otra ubicación», porque Apache crea en ella la de intentos (ver «Creación»). Apache tampoco debe poder escribir en la carpeta que contiene esta ni ser su propietario.
  - **Comprobaciones de la carpeta**, al crearla y en cada petición que la usa:
    - La carpeta no puede ser un enlace (solo se comprueba la última parte de la ruta).
    - Su propietario debe ser el usuario con el que corre PHP (solo con la extensión `posix`; sin ella queda una línea `CFW:` al crearla).
    - Ni el grupo ni otros pueden tener escritura sobre ella: 0700, 0750 o 0755. Se recomienda 0700, el modo con que la crea la aplicación: con 0750 o 0755, el grupo u otros usuarios pueden listar los contadores y ven cuántos hay, cuándo se escribieron y su número de fallos. No ven la IP ni el email: el nombre de cada contador es un HMAC-SHA256 de la IP y el email (en `cuenta`, del usuario) con un secreto derivado de la clave privada, y no se puede invertir sin ella.
    - La carpeta que la contiene no puede tener escritura para todos (por ejemplo 777 o `/tmp`), la misma regla que la carpeta de la clave privada. Se admite la de grupo, como el 775 de un clon.
    - En Windows solo se comprueba que no sea un enlace.
  - **Si una comprobación falla**, o no hay una clave privada válida (ver «Clave privada del cifrado»), el límite queda desactivado (fallo abierto) y el log tiene una línea `CFW:` en cada intento de login o de guardado en `cuenta` con la contraseña actual. Una ruta absoluta configurada que no cumple no vuelve a `cfw/data/intentos`: por ejemplo, `intentos.carpeta = "/tmp/algo"` deja el límite desactivado. Los contadores que son enlaces, enlaces duros o no son archivos normales se rechazan, sin escribir en ellos.
  - **Creación:** la carpeta se crea sola, en 0700, si existe la carpeta que la contiene y Apache puede escribir en ella. Si no (por ejemplo, con `cfw/data` en 555), créala a mano, siempre como Apache (con `intentos.carpeta`, usa esa ruta):
    ```
    sudo -u www-data mkdir -m 700 cfw/data/intentos
    ```
    - Si Apache no puede escribir en la carpeta que la contiene, dale la escritura solo mientras la creas, como para la clave (ver «Clave privada del cifrado»): con el grupo `www-data`, `chmod g+w` antes y `chmod g-w` después, pasándola antes a ese grupo con `chgrp www-data` si no lo es; si el propietario es Apache, `u+w` y `u-w`.
    - Si ya hay algo con ese nombre que no es una carpeta válida (por ejemplo, un enlace), bórralo antes, también como Apache y con la misma escritura temporal: `sudo -u www-data rm -r cfw/data/intentos`. Si no es de Apache, quita antes el bit sticky de `cfw/data` (ver «Carpeta `cfw/data` y permisos»).
      - Si falla con «Permission denied», es una carpeta con contenido de otro usuario (por ejemplo, de root) que Apache no puede vaciar. Apártala con otro nombre, también como Apache, con la escritura temporal y sin el bit sticky de `cfw/data`, y crea después la nueva: `sudo -u www-data mv cfw/data/intentos cfw/data/intentos.antigua` (u otro nombre que no exista). La aplicación no la usa, así que puede quedarse ahí: no la borres como root.
    - No ejecutes como root ninguna orden dentro de la carpeta que la contiene, tampoco con la escritura quitada: root seguiría un enlace que `www-data` hubiera puesto con ese nombre. Las órdenes sobre la propia carpeta que la contiene son seguras, porque Apache no puede escribir en la de encima (`cfw/`, o la de «Otra carpeta»).
  - Los contadores persisten tras los reinicios. Vaciar la carpeta, o un `rsync --delete` o un `git clean -X` del despliegue, los pone a cero. Otras aplicaciones que corran con el mismo usuario de PHP también pueden borrarlos o modificarlos, es decir, poner a cero el límite o provocar bloqueos.
  - Con varios servidores, cada uno cuenta por su lado.
  - `get_ip` usa solo `REMOTE_ADDR`. Detrás de un proxy o balanceador, todos los visitantes comparten esa IP, así que cualquiera puede bloquear el login de un email durante 15 minutos desde el último fallo.
- **Proxy que termina el TLS:** PHP no ve HTTPS y la cookie de sesión sale sin `Secure` (ver «Seguridad» en [README.md](README.md)). Configura el servidor para que PHP reciba `HTTPS=on` en esas peticiones.

## Actualizar un sitio
Para un sitio creado como en «Crear un sitio». Hazlo con el árbol de trabajo limpio y prueba el resultado en local antes de desplegarlo.
1. Mira la versión del sitio en `cfw/general/VERSION`; si no existe, es 1.x.
2. Trae las versiones de CFW y elige la nueva entre sus etiquetas:
   ```
   git fetch cfw
   git tag --sort=v:refname
   ```
   La lista incluye también las etiquetas propias del sitio, si las tiene.
3. Comprueba que la etiqueta, por ejemplo `2.0.0`, es la publicada: si CFW firma sus etiquetas, con `git tag -v 2.0.0`; si no, compara su commit (`git rev-parse "2.0.0^{commit}"`) con el de esa etiqueta en otra copia de CFW de confianza. Después, fusiónala:
   ```
   git merge 2.0.0
   ```
4. Resuelve los conflictos. Suelen salir en los archivos que el sitio adapta (el `.htaccess` raíz, `db.sql`, `css/`, `js/` e `img/`, y los `.md` de CFW si el sitio los cambió): conserva lo propio del sitio y añade lo nuevo de CFW. Revisa esos archivos aunque no tengan conflictos, con «Archivos que revisar a mano» de cada versión en [MIGRACION.md](MIGRACION.md). La configuración no debería dar conflictos, porque el sitio no toca `cfw/config.ini`: las opciones nuevas que quieras cambiar van en `cfw/specific/config.ini` o, si son del servidor, en `cfw/data/config.ini`.
5. Aplica los apartados de [MIGRACION.md](MIGRACION.md) de las versiones intermedias: los de todas las posteriores a la del sitio, hasta la nueva incluida, empezando por la más antigua. Incluyen las copias de `cfw/general/` que el sitio tenga en `cfw/specific/`, que no se actualizan solas.
6. Despliega como siempre (ver «Despliegue»).

- **Etiquetas:** las de CFW y las del sitio comparten nombres en el repositorio del sitio. No crees en el sitio etiquetas con números de versión de CFW, como `2.0.0`: `git fetch cfw` no podría traer la de CFW.
- **Sitios 1.x que son un clon de este repositorio con otros remotos** (por ejemplo, CFW como `origin`): renómbralos como en «Crear un sitio» antes del paso 2, para que CFW quede como `cfw` y el repositorio del sitio como `origin` (en ese ejemplo, primero `git remote rename origin cfw`, y después el del sitio a `origin`, o añádelo con `git remote add` si no está). Si ya no tiene ningún remoto de CFW, añádelo con `git remote add cfw https://github.com/MdeMoUcH/cfw.git`. Compruébalo con `git remote -v`.
- **Sitios 1.x que no son un clon de este repositorio:** no comparten historia con CFW, y `git merge` falla con «refusing to merge unrelated histories». Crea el sitio de nuevo como en «Crear un sitio», lleva a él `cfw/specific/` y los archivos que el sitio adapta, y aplica «2.0.0 (desde 1.x)» de [MIGRACION.md](MIGRACION.md). Desde ahí, ya se actualiza con estos pasos.
