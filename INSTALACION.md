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
- Lo propio del sitio va en `cfw/specific/` y en los archivos que el sitio adapta fuera de ella (ver «Qué se puede redefinir en `specific`» y «Qué queda fuera de `specific`» en [README.md](README.md)). No cambies `cfw/general/`: cada cambio ahí choca con los de CFW al actualizar. Cómo se construye sobre CFW está en [DESARROLLO.md](DESARROLLO.md).
- La configuración va en `config/`: `config/config.ini` lleva lo que es igual en todos los servidores, lo edita el sitio y se versiona; lo de cada servidor, con las contraseñas, va en `config/config-servidor.ini`, que no se versiona (ver «Puesta en marcha»).
- Después, sigue con «Puesta en marcha».

## Puesta en marcha
1. Carga `db.sql`. No crea ningún usuario.
2. Crea el primer administrador con el `INSERT` de ejemplo comentado en `db.sql`, con tu email y el hash de tu contraseña generado con `password_hash`.
3. Configura el sitio. CFW lee `config/config.ini` y después `config/config-servidor.ini`, que pisa al primero clave a clave. El segundo es opcional, y las secciones (`[general]`...) solo sirven para ordenar.
   - **`config/config.ini`**, versionado con el sitio, que lo edita: lo que es igual en todos los servidores, como `name`, `email`, `description`, `keywords`, `charset`, `lang_default`, `class.default`, `db.limit` y las claves propias del sitio. Nada secreto. CFW lo trae con sus valores por defecto.
   - **`config/config-servidor.ini`**, en cada servidor y sin versionar: los datos de la base de datos (`db.*`), `urlbase` (la URL del sitio), `desarrollo` y `analytics`, y `pathbase` (la ruta absoluta de la raíz del sitio) solo si no es la carpeta donde está instalado, que es su valor por defecto. Si a `urlbase` o `pathbase` les falta la barra final, se les añade. `pathbase` no cambia dónde se buscan `config/` ni `data/`, que van siempre en la raíz donde está instalado CFW. `desarrollo = true` muestra errores y detalles; no lo actives en un servidor público. Aquí van también `intentos.carpeta` y `cifrado.clave_privada`, si los usas, y normalmente las rutas de subida de `AdminHelper` (ver «Carpeta `data` y permisos»). Por ejemplo:
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
   - **Claves que faltan.** Si una clave de CFW no está en ninguno de los dos, se usa su valor por defecto del código: `desarrollo` (`false`), `charset` (`utf-8`), `name` (`CFW`), `lang_default` (`es`), `description`, `keywords` y `analytics` (vacíos), `db.limit` (`1000`) y `class.default` (`main`). Las que no tienen valor por defecto (`urlbase`, `email`, `db.host`, `db.user`, `db.pass` y `db.name`) quedan vacías y dejan en el log, en cada petición, una línea `CFW:` con sus nombres, sin valores. Al actualizar CFW, añade a `config/config.ini` las claves nuevas que liste [MIGRACION.md](MIGRACION.md).
   - Un fichero mal formado se ignora entero, como si no existiera, y deja una línea `CFW:` en el log con su ruta, sin su contenido. Si se ignora `config/config-servidor.ini`, el sitio se queda sin base de datos.
   - **Ubicaciones anteriores.** `cfw/config.ini`, de 1.x, ya no se lee ni se mueve, como tampoco `cfw/specific/config.ini`, `cfw/servidor.ini`, `cfw/data/config.ini` ni lo que quede en `cfw/data/` (salvo su `.htaccess`), de versiones de desarrollo de 2.0.0: mientras existan, dejan en el log una línea `CFW:` con sus rutas en cada petición. Muévelos como indica «2.0.0 (desde 1.x)» en [MIGRACION.md](MIGRACION.md).
   - **Permisos:** Apache solo tiene que poder leer `config/`. Lo recomendable es que la carpeta y sus archivos sean del usuario que despliega, con grupo `www-data`, la carpeta en 2750 (750 con el bit setgid) y los archivos en 640, y que Apache no pueda escribir en la raíz del sitio (ver «Carpeta `data` y permisos»): así no puede borrarlos ni sustituirlos.
     ```
     chgrp -R www-data config && chmod 2750 config && chmod 640 config/config.ini config/.htaccess
     ```
     Crea `config/config-servidor.ini` con umask 027, por ejemplo `(umask 027 && nano config/config-servidor.ini)`, para que no quede legible por todos antes del `chmod 640`, y haz con él lo mismo. `config/config.ini` está versionado: un `git pull` o `git merge` que lo cambie lo vuelve a crear con el grupo principal y el umask de quien despliega, y Apache puede dejar de leerlo (se ignora como mal formado y el sitio usa los valores por defecto del código). El bit setgid hace que conserve el grupo `www-data`; si el umask de quien despliega quita la lectura al grupo (por ejemplo, 077), o la carpeta no tiene el bit setgid, repite el `chgrp` y el `chmod` tras cada despliegue que cambie `config/`. Si el usuario de PHP puede modificar `config/` o alguno de sus archivos, cada petición deja una línea `CFW:` en el log con sus rutas. Esa comprobación solo mira la escritura: el propietario de un archivo puede cambiarle los permisos aunque no la tenga, así que tampoco deben ser de Apache.
   - **Valores con comillas o barras invertidas**, como muchas contraseñas: escríbelos entre comillas dobles, con `\"` por cada `"`, `\\` por cada `\` y `\${` en lugar de `${`, que si no se toma como una variable de entorno y desaparece sin error. Los demás caracteres (`;`, `!`, `=`, `(`, `'`, `$`...) salen tal cual entre comillas dobles. Por ejemplo, la contraseña `p"a\b${c` se escribe `db.pass = "p\"a\\b\${c"`. No uses comillas simples: no admiten `'`, y una `\` antes de la comilla hace que se pierdan sin error las claves que van detrás.
   - **Comprueba que se han leído bien**, sin mostrar los valores, desde la raíz del sitio y como Apache (ver «Órdenes como Apache» en «Carpeta `data` y permisos»):
     ```
     sudo -u www-data php -r 'foreach (array("config/config.ini", "config/config-servidor.ini") as $f) { echo "== ", $f, "\n"; $c = @parse_ini_file($f); if (!is_array($c)) { echo "no válido\n"; continue; } foreach ($c as $k => $v) echo $k, " ", is_array($v) ? "array" : strlen($v), "\n"; }'
     ```
     Debe listar todas las claves de cada fichero, cada una con la longitud que esperas para su valor (en bytes: una letra con tilde cuenta 2). Una clave que falte indica una línea mal escrita antes de ella, y una longitud distinta, una comilla o una barra mal escapadas.
4. Usa siempre HTTPS. El framework no lo fuerza, y el cifrado de las contraseñas no lo sustituye.
5. Comprueba que Apache aplica los `.htaccess` (ver «Protección con `.htaccess`»).

## Protección con `.htaccess`
Lo único que impide descargar `cfw/`, `config/` y `data/` son el `.htaccess` raíz, que los bloquea, y los de cada carpeta (`cfw/.htaccess`, `config/.htaccess` y `data/.htaccess`), que lo deniegan todo (`Require all denied`), como segunda barrera si se pierde la regla del raíz. Ahí están el código, `config/config-servidor.ini` con los datos de la base de datos, la clave privada del cifrado y `data/idiomas.ini`. Las reglas del raíz bloquean también una carpeta pública del sitio que se llame `config` o `data`. El `.htaccess` raíz también bloquea los `.sql`, `.md`, `.ini` y `.tpl` y los archivos ocultos, como `.git/`. **Si el servidor no lee los `.htaccess`, no da ningún error: sirve esos archivos tal cual.**
- **Apache con `AllowOverride None`.** Es el valor por defecto de Apache desde la 2.3.9, y el que trae Debian/Ubuntu para `/var/www/` en `apache2.conf`. Pon `AllowOverride All` en el `<Directory>` del sitio (o en su `VirtualHost`) y reinicia Apache. La imagen oficial de Docker `php:*-apache` ya lo hace en `docker-php.conf`; un Apache instalado con `apt` no.
- **nginx, Caddy, LiteSpeed sin soporte de `.htaccess` u otro servidor.** Ignoran los `.htaccess`. CFW no está preparado para ellos: habría que traducir a su configuración todas las reglas del `.htaccess` raíz, incluidos los bloqueos.
- **Comprobación.** Hazla tras instalar y tras cada cambio de servidor o de configuración. Las tres URL deben dar `403`:
  ```
  curl -s -o /dev/null -w '%{http_code}\n' https://tu-sitio/data/clave_privada.pem
  curl -s -o /dev/null -w '%{http_code}\n' https://tu-sitio/config/config-servidor.ini
  curl -s -o /dev/null -w '%{http_code}\n' https://tu-sitio/db.sql
  ```
  Un `200` significa que el archivo se descarga: corrige el servidor y, si era la clave privada, rótala (ver «Clave privada del cifrado»). Si era `config/config-servidor.ini`, cambia también la contraseña de la base de datos. Otra pista: si los `.htaccess` no se aplican, `/login` y `/admin` dan 404.
- **Comprobación de las cabeceras de seguridad.** Debe mostrar `X-Frame-Options: DENY` una sola vez. Si no sale, falta `mod_headers` (ver «Requisitos»):
  ```
  curl -sI https://tu-sitio/css/style.css | grep -i x-frame-options
  ```
- Aun con los `.htaccess` activos, lo más seguro es tener la clave privada fuera de la raíz web (ver «Otra ubicación» en «Clave privada del cifrado»).

## Carpeta `data` y permisos
Apache escribe en `data/`, en la raíz del sitio, la clave privada del cifrado y los contadores del límite de intentos (`data/intentos/`), si no se configura otra ubicación, y la opción de multiidioma, `data/idiomas.ini` (ver «Idiomas» en [README.md](README.md)). La configuración no va aquí, sino en `config/`, donde Apache solo puede leer (ver «Puesta en marcha»). Como Apache escribe en la carpeta, podría borrar o sustituir lo que no es suyo, como `data/.htaccess`: lo impide el bit sticky de la orden de abajo.

Antes de usarla, da a Apache permiso sobre la carpeta. La aplicación crea el fichero la primera vez que guardas. Esta orden, como todas las de este documento con rutas relativas, se ejecuta desde la raíz del sitio:
```
chgrp www-data data && chmod 1770 data
```
- Repite esta orden tras un clon nuevo, porque git no conserva el grupo ni el modo. `chgrp` puede requerir privilegios.
- **El bit sticky (el `1` de `1770`)** impide que Apache borre o renombre en `data` lo que no es suyo. Sin él, un fallo que le dejara escribir en `data` con un nombre elegido le bastaría para borrar o sustituir `data/.htaccess`, que protege lo que guarda la carpeta. Para que proteja:
  - La carpeta `data` no puede ser de Apache.
  - Lo que crea Apache en ella (`clave_privada.pem`, `idiomas.ini` e `intentos/`) tiene que ser suyo, como queda al crearlo él; si no, no podrá sustituirlo ni borrarlo.
  - Para borrar o apartar como Apache algo de `data` que no es suyo (por ejemplo, una clave privada o una carpeta `intentos` de otro usuario), quita el bit a la carpeta mientras dura la operación: `chmod -t data` antes y `chmod +t data` después, con `sudo` si no eres su propietario.
- Apache no debe ser propietario ni tener escritura, tampoco de grupo, sobre la raíz del sitio, `cfw/`, `config/`, `js/` ni el resto de archivos y carpetas, salvo `data/`, las carpetas de subida y las rutas propias de `intentos.carpeta` y `cifrado.clave_privada` dentro de `cfw/` (ver abajo). Ahí están el código, la configuración (`config/`), los textos (que se pintan sin escapar) y `js/cifrado.js`, que cifra las contraseñas en el navegador: quien pueda escribir en ellos puede inyectar código en todo el sitio, incluida la administración, o capturar las contraseñas.
  - Las carpetas de subida son las que una subclase de `AdminHelper` toma de la configuración (normalmente de `config/config-servidor.ini`): `<config_name>.path` para `saveFile` y `<config_name>.img.path` para `saveImage`, con barra final, que aquí no se añade sola, a diferencia de `urlbase` y `pathbase`. Apache tiene que poder escribir en ellas. Deben ser carpetas solo para subidas, sin código ni otros archivos del sitio: no pueden ser la raíz, `config/`, `css/`, `img/` ni `js/`, ni estar en `cfw/` o dentro de ella (incluidas `cfw/general` y `cfw/specific`). El CFW base no configura ninguna.
  - Para comprobarlo, ejecuta esta orden desde la raíz del sitio. No debe mostrar nada, salvo las carpetas de subida y su contenido, y las rutas de `intentos.carpeta` o `cifrado.clave_privada` si las pones dentro de `cfw/`, que la regla de `cfw/` del `.htaccess` raíz también bloquea (los paréntesis hacen falta para que `-print` cubra las dos condiciones, y el `-print` explícito, para que no salga `data`):
    ```
    sudo -u www-data find . -path ./data -prune -o \( -writable -o -user www-data \) -print
    ```
    En esas rutas también salen los contadores de intentos de la carpeta y, si Apache puede escribir en ella o es su propietario, la carpeta que las contiene. Esa carpeta tiene que ser propia, solo para esas rutas, nunca `cfw/`, `cfw/general`, `cfw/specific` ni `config/`, aunque después le quites la escritura: la carpeta de intentos y la clave las crea siempre Apache, también a mano, así que tiene que poder escribir en ella al menos mientras tanto (ver «Creación» en «Despliegue» y «Clave privada del cifrado»). Si sale una clave privada en una ruta que sirve la web, sin la protección de `cfw/`, `config/` o `data/`, hay que moverla a una ruta protegida y rotarla (ver «Otra ubicación» y «Para rotarla» en «Clave privada del cifrado»).
    Un «Permission denied» (sale por stderr) indica una carpeta que Apache no puede leer y que, por tanto, no ha quedado comprobada.
- **Órdenes como Apache.** Lanza las órdenes `sudo -u www-data` de este documento con un `sudo` que use `use_pty`. Sin él, la orden comparte tu terminal: un `www-data` comprometido podría engancharse a la orden con `ptrace`, porque corre con su mismo usuario, o meter órdenes en tu terminal con `TIOCSTI`, que tu shell ejecutaría al terminar con tus permisos (los de root, si la lanzas desde una terminal de root). Con `Defaults use_pty`, sudo le da una pseudoterminal propia; es el valor por defecto desde sudo 1.9.14 y viene en los sudoers de las versiones actuales de Debian y Ubuntu. Para comprobarlo, en esa misma terminal, `tty` y `sudo -u www-data tty` deben mostrar terminales distintas. Si muestran la misma, añade `Defaults use_pty` con `visudo`.

## Clave privada del cifrado
`data/clave_privada.pem` por defecto, RSA de 3072 bits.
- Se genera sola en el primer uso, con permisos 0600, si Apache puede escribir en `data/` (ver «Carpeta `data` y permisos»). Si no puede, o la clave no es válida, se muestra un aviso en lugar de los formularios con contraseña (en `users` solo se puede editar sin cambiarla) y el log una línea `CFW:`; un archivo no válido no se sobrescribe.
- También se puede generar a mano, siempre como Apache. Así la clave pasa de `openssl` directamente a su archivo, que queda de `www-data` y en 0600, sin copias en temporales ni en el historial:
  ```
  sudo -u www-data sh -c 'umask 077 && openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -out data/clave_privada.pem'
  ```
  - Apache tiene que poder escribir en la carpeta. Si no puede (por ejemplo, con `data` en 550), dale la escritura solo mientras la borras y la generas, y quítasela después. Con la carpeta del grupo `www-data`, como en «Carpeta `data` y permisos», es `chmod g+w data` antes y `chmod g-w data` después, con `sudo` si no eres su propietario; si no es de ese grupo (por ejemplo, tras un clon nuevo), pásala antes con `chgrp www-data data`. Si el propietario es Apache, `u+w` y `u-w`.
  - Si ya hay algo con ese nombre (por ejemplo, una clave no válida o una carpeta), bórralo antes, también como Apache: `sudo -u www-data rm -r data/clave_privada.pem`. Si no es de Apache, quita antes el bit sticky de `data` (ver «Carpeta `data` y permisos»).
  - No ejecutes como root ninguna orden dentro de la carpeta de la clave ni sobre la clave, tampoco con la escritura quitada: root seguiría un enlace que `www-data` hubiera puesto con ese nombre y acabaría sobrescribiendo otro archivo del sistema o dándoselo a `www-data`. Las órdenes sobre la propia carpeta, como las de arriba, son seguras, porque Apache no puede escribir en la raíz del sitio.
  - Comprueba que el archivo existe antes de seguir: algunas versiones de `openssl` terminan sin error aunque no hayan podido escribirlo. Se acepta una clave RSA de 2048 bits o más.
- **Permisos exigidos.** Si no se cumplen, la clave se rechaza sin sobrescribirla (aviso en los formularios y una línea `CFW:` en el log):
  - El archivo debe ser un archivo normal, no un enlace, cuyo propietario sea el usuario con el que corre PHP (`www-data`, o el de la cuenta con PHP-FPM o suEXEC; solo se comprueba con `posix`, ver abajo). Ni el grupo ni otros pueden tener escritura sobre él, y otros tampoco lectura: 0600, 0640, 0400 o 0440. Para corregirlo, bórrala como Apache (`sudo -u www-data rm -r data/clave_privada.pem`; en `data`, quitando antes el bit sticky de la carpeta, ver «Carpeta `data` y permisos»), y genérala de nuevo, con la escritura temporal si hace falta (ver arriba).
  - La carpeta no puede tener escritura para todos (por ejemplo 777 o `/tmp`). Se admite la de grupo, como el 775 de un clon o el 1770 con el grupo de Apache (ver «Carpeta `data` y permisos»), y cualquier propietario, también root. Para corregirlo: `chmod o-w data`. La carpeta de los contadores de intentos sigue la misma regla para la carpeta que la contiene (ver «Despliegue»).
  - El propietario solo se comprueba con la extensión `posix`, que se recomienda tener. Sin ella la garantía es más débil: se acepta una clave de cualquier propietario si cumple el resto (archivo normal, sin escritura de grupo ni de otros ni lectura de otros, carpeta sin escritura para todos), y al generarla queda una línea `CFW:` en el log que lo avisa. En Windows no se comprueban propietario ni permisos, solo que no sea un enlace.
- Para rotarla, bórrala como Apache. Si la clave no es de Apache y está en `data/`, quita antes el bit sticky de la carpeta: `chmod -t data` antes y `chmod +t data` después, con `sudo` si no eres su propietario (ver «Carpeta `data` y permisos»); sin la extensión `posix` una clave de otro propietario se acepta, así que compruébalo con `ls -l`. Si Apache puede escribir en la carpeta, se crea otra en la siguiente petición; si no, dale la escritura temporal para borrarla y generar otra a mano (ver arriba). Solo fallan una vez los formularios que estaban abiertos. Rotarla también levanta los bloqueos en curso del límite de intentos, porque los nombres de los contadores dependen de ella (ver «Límite de intentos» en «Despliegue»).
- En `data/` solo la protegen el `.htaccess` raíz y `data/.htaccess`. Si el servidor no los aplica (nginx o `AllowOverride None`), se puede descargar sin que se avise de nada: ver «Protección con `.htaccess`». Con varios servidores, todos deben compartir la misma clave.
- **Otra ubicación**, por ejemplo fuera de la raíz web: añade a `config/config-servidor.ini` la opción `cifrado.clave_privada` con la ruta absoluta del archivo, en su propia sección:
  ```
  [cifrado]
  cifrado.clave_privada = "/srv/cfw-claves/clave_privada.pem"
  ```
  - Es opcional: si la línea no está, se usa `data/clave_privada.pem`. Si está, debe ser una ruta absoluta (`/…`; en Windows, `C:\…` o `C:/…`); se ignoran los espacios del principio y del final. Un valor vacío, relativo o que `parse_ini_file` convierte en vacío (`off`, `no`, `false`, `none`) no se acepta: los formularios muestran el aviso y el log una línea `CFW:`. Para volver a la ruta por defecto, borra o comenta la línea.
  - PHP debe poder leer el archivo (también con `open_basedir`, si lo usas). Se aplican los mismos permisos exigidos de arriba. Para que se genere solo, Apache tiene que poder escribir en la carpeta. Si no, genérala a mano como se explica arriba, con esa ruta y dando la escritura temporal en su carpeta, no en `data` (si no es del grupo `www-data`, pásala antes a ese grupo, por ejemplo con `sudo chgrp www-data /srv/cfw-claves`). Después la carpeta puede quedar sin escritura para Apache, que es lo más seguro. Apache tampoco debe poder escribir en la carpeta que la contiene ni ser su propietario, porque podría cambiarla por un enlace. No uses una carpeta compartida ni una dentro de la raíz web y fuera de `cfw/` y `data/`.

## Despliegue
- **Configuración del servidor** (`config/config-servidor.ini`) y **`data/`**: conserva el fichero y el contenido de la carpeta entre despliegues. Un `rsync --delete` o un `git clean -X` los borran: sin el fichero, el sitio se queda sin base de datos, y sin `data/`, se genera otra clave privada y se pierden los contadores de intentos y el multiidioma.
- **Multiidioma** (`data/idiomas.ini`):
  - Conserva el fichero entre despliegues. Un `rsync --delete` o un `git clean -X` lo borran: el multiidioma se desactiva y `/admin/idiomas` muestra un aviso.
  - La opción está pensada para un solo servidor: con varios, el fichero no se comparte entre ellos.
  - Tiene que ser un archivo normal. Un enlace (también roto), un FIFO o una carpeta en su lugar desactivan el multiidioma: `/admin/idiomas` muestra el aviso y queda una línea `CFW:` en el log en cada petición. Guardar esa página lo sustituye por un archivo normal, salvo si es una carpeta o, con el bit sticky en `data`, si no es de Apache: entonces el guardado falla. Bórralo a mano como Apache, quitando antes el bit si no es suyo (ver «Carpeta `data` y permisos»).
  - Se crea con modo 0640, salvo que una ACL por defecto de `data` dé otro sin permisos para otros (por ejemplo, 0660). Si la ACL da permisos para otros, o quita al propietario la lectura o la escritura, el guardado falla con el mensaje de error y una línea `CFW:` en el log.
- **Clave privada:** con varios servidores, todos deben compartir la misma clave (ver «Clave privada del cifrado»).
- **Límite de intentos** (ver «Seguridad» en [README.md](README.md)): los contadores son archivos en `data/intentos/`, si no se configura otra carpeta.
  - **Otra carpeta**, por ejemplo fuera de la raíz web: añade a `config/config-servidor.ini` la opción `intentos.carpeta`, en su propia sección:
    ```
    [intentos]
    intentos.carpeta = "/srv/cfw-intentos/intentos"
    ```
    Las reglas de la ruta son las de `cifrado.clave_privada`: absoluta (`/…`; en Windows, `C:\…` o `C:/…`) y sin los espacios del principio y del final; además se quitan las barras finales. A diferencia de la clave, un valor no válido (vacío, `off`, `no`, `false`, `none`, relativo o `/`) no desactiva nada: se usa `data/intentos` y queda una línea `CFW:` en el log en cada intento de login o de guardado en `cuenta` con la contraseña actual.
    La carpeta que la contiene (en el ejemplo, `/srv/cfw-intentos`) tiene que ser propia, como la de la clave en «Otra ubicación», porque Apache crea en ella la de intentos (ver «Creación»). Apache tampoco debe poder escribir en la carpeta que contiene esta ni ser su propietario.
  - **Comprobaciones de la carpeta**, al crearla y en cada petición que la usa:
    - La carpeta no puede ser un enlace (solo se comprueba la última parte de la ruta). Una ruta configurada que acaba en `.` o `..` se rechaza.
    - Su propietario debe ser el usuario con el que corre PHP (solo con la extensión `posix`; sin ella queda una línea `CFW:` al crearla).
    - Ni el grupo ni otros pueden tener escritura sobre ella: 0700, 0750 o 0755. Se recomienda 0700, el modo con que la crea la aplicación: con 0750 o 0755, el grupo u otros usuarios pueden listar los contadores y ven cuántos hay, cuándo se escribieron y su número de fallos. No ven la IP ni el email: el nombre de cada contador es un HMAC-SHA256 de la IP y el email (en `cuenta`, del usuario) con un secreto derivado de la clave privada, y no se puede invertir sin ella.
    - La carpeta que la contiene no puede tener escritura para todos (por ejemplo 777 o `/tmp`), la misma regla que la carpeta de la clave privada. Se admite la de grupo, como el 775 de un clon.
    - En Windows solo se comprueba que no sea un enlace.
  - **Si una comprobación falla**, o no hay una clave privada válida (ver «Clave privada del cifrado»), el límite queda desactivado (fallo abierto) y el log tiene una línea `CFW:` en cada intento de login o de guardado en `cuenta` con la contraseña actual. Una ruta absoluta configurada que no cumple no vuelve a `data/intentos`: por ejemplo, `intentos.carpeta = "/tmp/algo"` deja el límite desactivado. Los contadores que son enlaces, enlaces duros o no son archivos normales se rechazan, sin escribir en ellos; se borran cuando un login o una contraseña actual correctos limpian ese contador, sin tocar el destino de un enlace (una carpeta no se borra). La limpieza de los contadores caducados solo borra archivos normales con nombre de contador, no sigue enlaces y se detiene si la carpeta cambia mientras tanto.
  - **Creación:** la carpeta se crea sola, en 0700, si existe la carpeta que la contiene y Apache puede escribir en ella. Si no (por ejemplo, con `data` en 555), créala a mano, siempre como Apache (con `intentos.carpeta`, usa esa ruta):
    ```
    sudo -u www-data mkdir -m 700 data/intentos
    ```
    - Si Apache no puede escribir en la carpeta que la contiene, dale la escritura solo mientras la creas, como para la clave (ver «Clave privada del cifrado»): con el grupo `www-data`, `chmod g+w` antes y `chmod g-w` después, pasándola antes a ese grupo con `chgrp www-data` si no lo es; si el propietario es Apache, `u+w` y `u-w`.
    - Si ya hay algo con ese nombre que no es una carpeta válida (por ejemplo, un enlace), bórralo antes, también como Apache y con la misma escritura temporal: `sudo -u www-data rm -r data/intentos`. Si no es de Apache, quita antes el bit sticky de `data` (ver «Carpeta `data` y permisos»).
      - Si falla con «Permission denied», es una carpeta con contenido de otro usuario (por ejemplo, de root) que Apache no puede vaciar. Apártala con otro nombre, también como Apache, con la escritura temporal y sin el bit sticky de `data`, y crea después la nueva: `sudo -u www-data mv data/intentos data/intentos.antigua` (u otro nombre que no exista). La aplicación no la usa, así que puede quedarse ahí: no la borres como root.
    - No ejecutes como root ninguna orden dentro de la carpeta que la contiene, tampoco con la escritura quitada: root seguiría un enlace que `www-data` hubiera puesto con ese nombre. Las órdenes sobre la propia carpeta que la contiene son seguras, porque Apache no puede escribir en la de encima (la raíz del sitio, o la de «Otra carpeta»).
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
4. Resuelve los conflictos. Suelen salir en los archivos que el sitio adapta (el `.htaccess` raíz, `db.sql`, `css/`, `js/` e `img/`, y los `.md` de CFW si el sitio los cambió): conserva lo propio del sitio y añade lo nuevo de CFW. Revisa esos archivos aunque no tengan conflictos, con «Archivos que revisar a mano» de cada versión en [MIGRACION.md](MIGRACION.md). Si CFW borra un fichero que el sitio cambió, como los `main.ini` de `cfw/specific/lang/` en 2.0.0, git da un conflicto «modify/delete»: consérvalo con `git add` o bórralo con `git rm`, según diga [MIGRACION.md](MIGRACION.md) para esa versión. `config/config.ini` es del sitio y puede dar conflictos: conserva sus valores y añade las claves nuevas que liste [MIGRACION.md](MIGRACION.md) para cada versión. Lo del servidor va en `config/config-servidor.ini`, que no se versiona y no da conflictos.
5. Aplica los apartados de [MIGRACION.md](MIGRACION.md) de las versiones intermedias: los de todas las posteriores a la del sitio, hasta la nueva incluida, empezando por la más antigua. Incluyen las copias de `cfw/general/` que el sitio tenga en `cfw/specific/`, que no se actualizan solas.
6. Despliega como siempre (ver «Despliegue»).

- **Etiquetas:** las de CFW y las del sitio comparten nombres en el repositorio del sitio. No crees en el sitio etiquetas con números de versión de CFW, como `2.0.0`: `git fetch cfw` no podría traer la de CFW.
- **Sitios 1.x que son un clon de este repositorio con otros remotos** (por ejemplo, CFW como `origin`): renómbralos como en «Crear un sitio» antes del paso 2, para que CFW quede como `cfw` y el repositorio del sitio como `origin` (en ese ejemplo, primero `git remote rename origin cfw`, y después el del sitio a `origin`, o añádelo con `git remote add` si no está). Si ya no tiene ningún remoto de CFW, añádelo con `git remote add cfw https://github.com/MdeMoUcH/cfw.git`. Compruébalo con `git remote -v`.
- **Sitios 1.x que no son un clon de este repositorio:** no comparten historia con CFW, y `git merge` falla con «refusing to merge unrelated histories». Crea el sitio de nuevo como en «Crear un sitio», lleva a él `cfw/specific/` y los archivos que el sitio adapta, y aplica «2.0.0 (desde 1.x)» de [MIGRACION.md](MIGRACION.md). Desde ahí, ya se actualiza con estos pasos.
