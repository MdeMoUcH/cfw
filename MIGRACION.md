# Migrar un sitio basado en CFW
Lo que hay que revisar al actualizar CFW en un proyecto ya construido con él, versión a versión. Para saber cómo funciona CFW, ver [README.md](README.md); para requisitos, permisos y despliegue, [INSTALACION.md](INSTALACION.md); para los pasos con git, «Actualizar un sitio» en [INSTALACION.md](INSTALACION.md).

## Numeración
La versión de CFW está en `cfw/general/VERSION`, con tres números (por ejemplo, `2.0.0`), y la etiqueta de git de cada versión se llama igual. Los sitios hechos con versiones anteriores no tienen ese archivo: son «1.x».
- El primer número cambia si un sitio tiene que tocar algo para actualizar.
- El segundo, si hay funciones nuevas sin romper nada.
- El tercero, en las correcciones.

Debajo hay un apartado por versión, con la más reciente arriba, que dice qué revisar al llegar a ella desde la anterior. Desde 2.0.0, cada apartado lista también las claves nuevas que hay que añadir a `config/config.ini`. Al actualizar, aplica los apartados de todas las versiones posteriores a la del sitio, hasta la nueva incluida, empezando por la más antigua (ver «Actualizar un sitio» en [INSTALACION.md](INSTALACION.md)).

## 2.0.0 (desde 1.x)
2.0.0 exige PHP 8.2, la extensión `openssl` (sin ella no hay login) y Apache 2.4.10 con `mod_headers`, y se recomienda la extensión `posix`. La configuración y los datos pasan a dos carpetas de la raíz: `config/`, donde Apache solo lee, y `data/`, donde escribe. Apache necesita escritura en `data/` para generar la clave privada del cifrado: si no la tiene, el login muestra un aviso en lugar del formulario. Prepárala después de desplegar con `chgrp www-data data && chmod 1770 data`. Ver «Requisitos», «Carpeta `data` y permisos» y «Clave privada del cifrado» en [INSTALACION.md](INSTALACION.md).

### Archivos que revisar a mano
Cada proyecto adapta estos ficheros, que no están en `specific` y no se actualizan solos:
- `cfw/config.ini` desaparece. La configuración va en dos ficheros de `config/`, que se fusionan clave a clave: `config/config.ini` (lo de CFW y del sitio que es igual en todos los servidores, versionado) y `config/config-servidor.ini` (lo de cada servidor y lo secreto, opcional y sin versionar), que pisa al primero. Ver el paso 3 de «Puesta en marcha» en [INSTALACION.md](INSTALACION.md). Reparte el `cfw/config.ini` actual entre los dos:
  - En cada servidor, **antes de desplegar**, crea `config/config-servidor.ini` con las claves de servidor del `cfw/config.ini` actual: `db.*`, `urlbase`, `pathbase`, `desarrollo`, `analytics` y, si las usas, las rutas de subida de `AdminHelper`. Créalo con umask 027, por ejemplo `mkdir -p config && (umask 027 && nano config/config-servidor.ini)`. Si el servidor despliega con git y tiene `cfw/config.ini` modificado, descarta ese cambio después de copiarlo. El fichero no se versiona, y `rsync --delete` y `git clean -X` lo borran (ver «Despliegue»). Después de desplegar, pon `config/` y sus archivos como indica «Permisos» en el paso 3 de «Puesta en marcha» (carpeta en 2750, con el bit setgid, y archivos en 640, del usuario que despliega y del grupo `www-data`).
  - Pon en `config/config.ini`, que trae CFW, lo propio del sitio: `name`, `description`, `keywords`, `lang_default`, `class.default`, `email` y las claves propias. Se versiona con el sitio: nada secreto.
  - El `git merge` borra `cfw/config.ini`. Si el sitio lo había cambiado, git da un conflicto «modify/delete»: cuando hayas llevado su contenido a `config/`, bórralo con `git rm cfw/config.ini`. Mientras exista, no se lee y deja en el log una línea `CFW:` con su ruta en cada petición.
  - `langs` desaparece. `pathbase` sobra si es la raíz del sitio, que es su valor por defecto, y no cambia dónde se buscan `config/` ni `data/`. Las opciones nuevas y opcionales `intentos.carpeta` y `cifrado.clave_privada`, para guardar los contadores del límite de intentos y la clave privada en otra ubicación, van en `config/config-servidor.ini` (ver «Límite de intentos» en «Despliegue» y «Clave privada del cifrado» en [INSTALACION.md](INSTALACION.md)).
  - `urlbase` y `pathbase` reciben la barra final si les falta. Un sitio que ponía `urlbase` sin barra y añadía `/` en sus plantillas obtendrá `//`.
  - Si `cfw/config.ini` tuvo contraseñas reales en el repositorio, cámbialas: siguen en su historia.
  - Los valores con comillas o barras invertidas van entre comillas dobles, y hay que comprobar que el fichero se ha leído bien: ver «Valores con comillas o barras invertidas» y «Comprueba que se ha leído bien» en el paso 3 de «Puesta en marcha».
  - Un fichero mal formado se ignora entero, con una línea `CFW:` en el log.
  - Un `AppConf` propio en `cfw/specific/class/` no lee `config/`: quítalo o llévale los cambios.
  - Un servidor con una versión de desarrollo de 2.0.0 puede tener `cfw/specific/config.ini`, `cfw/servidor.ini`, `cfw/data/config.ini` o `cfw/data/`. Tampoco se leen ni se mueven solos, y dejan la misma línea `CFW:` en el log mientras existan. Sin moverlos, el sitio se queda sin base de datos y genera otra clave privada. Llévalos directamente a su sitio:
    - `cfw/servidor.ini` o `cfw/data/config.ini`, antes de desplegar, a `config/config-servidor.ini`, conservando grupo y modo (`mkdir -p config && cp -p cfw/servidor.ini config/config-servidor.ini`, o `cfw/data/config.ini` en lugar de `cfw/servidor.ini`), y bórralos después de desplegar. Después, pon `config/` y sus archivos como indica «Permisos» en el paso 3 de «Puesta en marcha» de [INSTALACION.md](INSTALACION.md).
    - Las claves de `cfw/specific/config.ini`, encima de las de `config/config.ini`; quítalo con `git rm`.
    - Lo de `cfw/data/`, después de desplegar y preparar `data/`, como Apache, que es su propietario (ver «Órdenes como Apache» en «Carpeta `data` y permisos» de [INSTALACION.md](INSTALACION.md)): `sudo -u www-data mv cfw/data/clave_privada.pem data/` y `sudo -u www-data mv cfw/data/idiomas.ini data/`, y borra los contadores con `sudo -u www-data rm -r cfw/data/intentos` (no la muevas: falla si la web ya ha creado `data/intentos/`). Si algo de `cfw/data` no es de Apache, quita antes el bit sticky de esa carpeta (`chmod -t cfw/data`). Quita la carpeta con `rmdir cfw/data` en cuanto se vacíe.
    - Los `textos.ini` propios de `cfw/specific/lang/` que citen `cfw/data` pasan a `data`.
- El `.htaccess` raíz.
  - El bloque `<IfModule mod_headers.c>` de las cabeceras de seguridad es nuevo: cópialo y activa `mod_headers` (ver «Requisitos» en [INSTALACION.md](INSTALACION.md)). Sin el módulo no da error, pero los estáticos y los errores de Apache quedan sin esas cabeceras.
  - Apache solo pone cada cabecera si la respuesta no la trae ya.
  - Responde 403 a los archivos ocultos (salvo `.well-known`), a todo `cfw/`, `config/` y `data/`, y a los `.ini`, `.sql`, `.tpl` y `.md`. La regla de `config` y `data`, `RewriteRule ^(config|data)(/|$) - [F,NC,L]`, va después de la de `cfw`: cópiala. Un sitio con una carpeta pública `config/` o `data/` deja de servirla y tiene que renombrarla.
  - `<Files .htaccess>` pasa a `<Files ".htaccess">` con `Require all denied`, en lugar de `order allow,deny` y `deny from all`, que necesitaban `mod_access_compat`.
  - Se quita la regla que redirigía `/uploads` a `http://localhost/`. Si tu sitio guarda en `uploads/` archivos que no deben descargarse, llévalos fuera de la raíz web o bloquéalos con su propia regla (por ejemplo, `RewriteRule ^uploads(/|$) - [F,NC,L]`).
  - Se quitan las reglas de `/login/register` y `/login/restorepassword`, que no tenían método.
  - Las reglas de `/login`, `/login/logout` y `/admin` se anclan: `^login/logout/?$`, `^login/?$` y `^admin/?$`. Antes, `/loginfoo`, `/login/<otra cosa>`, `/login/logout/<otra cosa>` y `/adminfoo` caían en ellas; ahora dan 404. Cópialas, y si una URL propia dependía de esas coincidencias, dale su regla.
- `cfw/.htaccess`, `config/.htaccess` y `data/.htaccess`, nuevos (`Require all denied`): cópialos también. Son la segunda barrera del código, de `config/config-servidor.ini`, con los datos de la base de datos, y de la clave privada, si el `.htaccess` raíz pierde sus reglas. CFW solo carga `cfw/` desde PHP, así que no bloquea nada que el sitio necesite.
- `.gitignore`: añade `config/config-servidor.ini`, `data/*` y `!data/.htaccess`.
- `db.sql`:
  - La columna `pass` debe ser al menos `varchar(255)`.
  - `usuarios` tiene la columna generada nueva `email_activo` (el email si `borrado = 0`, y si no `NULL`), con un índice `UNIQUE`, y `email` pasa a tener un índice normal: el email no se repite entre los usuarios no borrados, y el de un usuario borrado se puede reutilizar. En la BD de cada servidor:
    1. Busca los duplicados entre los usuarios no borrados, con `SELECT email, COUNT(*) FROM usuarios WHERE borrado = 0 GROUP BY email HAVING COUNT(*) > 1;`. Las mayúsculas cuentan como iguales.
    2. Resuélvelos: elige la fila que se queda y cambia el email de las demás (por ejemplo, `UPDATE usuarios SET email = CONCAT(email, '.duplicado-', id) WHERE id IN (...);`) o bórralas.
    3. Añade la columna y los índices: ``ALTER TABLE usuarios ADD COLUMN `email_activo` varchar(255) GENERATED ALWAYS AS (IF(`borrado` = 0, `email`, NULL)) STORED, ADD UNIQUE KEY `email_activo` (`email_activo`), ADD KEY `email` (`email`);``. Si la tabla ya tiene `UNIQUE KEY email` (una versión de desarrollo de 2.0.0), pon `DROP INDEX email,` justo después de `ALTER TABLE usuarios`. Falla con el error 1062 mientras quede algún duplicado.
  - Sin la columna, `Usuarios` no la echa en falta, pero el email de un usuario borrado no se puede reutilizar si la tabla tiene `UNIQUE KEY email`, y sin ningún índice único no hay protección contra dos altas simultáneas.
  - Mientras haya duplicados, esas cuentas no se pueden guardar desde `users` ni desde `cuenta`: se rechazan con «Ya existe un usuario con ese e-mail.».
  - `Usuarios::insert` y `Usuarios::update` comprueban el email solo contra los usuarios no borrados.
  - Un `INSERT INTO usuarios VALUES (...)` sin lista de columnas da el error 1136 por la columna nueva: pon la lista de columnas.
  - Restaurar a mano un usuario borrado (`borrado = 0`) da el error 1062 si otro usuario activo tiene ya su email.
  - Con `utf8mb4` en MyISAM, o en InnoDB con `ROW_FORMAT=COMPACT`, un `varchar(255)` es demasiado largo para los índices de `email` y `email_activo` («key too long»): acorta las columnas o usa InnoDB `DYNAMIC`.
- `css/`, `js/` e `img/`.
- Las copias propias de páginas, clases, plantillas y textos en `cfw/specific/`.

### Peticiones y acceso
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
- `login/logout`, y `delete` y `editfield` de `AdminHelper`, solo actúan por POST.
- Los métodos de `App` y `Page` (y sus redefiniciones) ya no son accesibles por URL.
- `get_ip` usa solo `REMOTE_ADDR` y deja de usar `X-Forwarded-For`.
- `index.php` solo despacha clases que hereden de `Page`. Las demás dan 404, también las de `cfw/specific/pages/`.
- `index.php` envía `X-Frame-Options: DENY`, `Content-Security-Policy: frame-ancestors 'none'`, `X-Content-Type-Options: nosniff` y `Referrer-Policy: strict-origin-when-cross-origin`:
  - Ninguna página se puede enmarcar, tampoco desde el propio sitio. Para enmarcar las propias, redefine en la página `X-Frame-Options` y `Content-Security-Policy` con `header()`, por ejemplo `SAMEORIGIN` y `frame-ancestors 'self'`. Apache no las pisa.
  - Una CSP propia enviada desde PHP sustituye a la del framework y tiene que incluir `frame-ancestors`.
  - Con `nosniff`, los scripts y estilos servidos con un `Content-Type` incorrecto dejan de cargarse.

### Páginas, clases y funciones
- `saveImage`, `saveFile`, `guardarCategorias` y `load_template` pasan a `protected`.
- `saveFile` solo acepta `tpl` y `css`, salvo que se redefina `$extensiones_permitidas`, y decide la extensión por el contenido, no por el MIME ni el nombre que envía el cliente (ver «Administración con `AdminHelper`» en [DESARROLLO.md](DESARROLLO.md)): un CSS con algo que parezca una etiqueta se guarda como `tpl`, y un `.tpl` sin etiquetas o un archivo que no sea UTF-8 se rechazan. Los nombres subidos se normalizan y `saveImage` pone siempre la extensión del tipo real.
- Se eliminan `comprobarInvitado`, `getLastUserByFkIdUsuario` y `getUsersByFkIdUsuario` de `Usuarios`.
- `Usuarios` tiene el método `protected` nuevo `emailRepetido`: un método con el mismo nombre en una subclase lo redefine.
- `Usuarios::getUserNameById` devuelve `text_usuario_desconocido` («Usuario desconocido») con un usuario borrado, como con uno inexistente; antes devolvía su email. Un sitio que lo use para mostrar el autor de contenidos antiguos verá ese texto en los de usuarios borrados.
- `Usuarios::setLastVisit($id)` pierde el parámetro `$timezone`, que no se guardaba (no hay columna `timezone`), e incrementa `visitas` en el propio UPDATE (`visitas = visitas + 1`): dos logins simultáneos ya no pierden una visita. Con un usuario borrado o inexistente no escribe y devuelve `false` con `text_fila_no_editable`, como antes. Una llamada que aún pase el segundo argumento sigue funcionando: PHP lo ignora.
- PHP 8.2 da `Deprecated` con las propiedades dinámicas: las subclases de `cfw/specific/` deben declarar las suyas, con una visibilidad compatible con la del padre. `private` da un fatal, y `protected` también si el padre la declara `public`, como `$tabla` en `Orm` o `$vars` en `Page`.
- Se eliminan la función global `saveImage` y `cfw/general/utils/images.php` (`saveImageProductos`). Usa `AdminHelper::saveImage`.
- `main` solo pinta las plantillas de `$a_plantillas_permitidas`. Una `main` propia en `cfw/specific/pages/main.php` sustituye entera a la de `general`, sin heredar nada (ver los límites de «Qué se puede redefinir en `specific`» en [README.md](README.md)), así que debe partir de la `main` nueva y declarar su propia `$a_plantillas_permitidas`, con `portada`: una copiada de la versión anterior no tiene esa lista. Ampliar la propiedad solo vale en una subclase de `main` con otro nombre.
- `Page` tiene métodos `protected` nuevos: `redirigir`, `guardarFlash`, `leerFlash`, `prepararCifrado`, `leerPassCifradas` y `textoFalloCifrado`. Un método con el mismo nombre en una subclase los redefine, y con una firma o una visibilidad incompatibles da un error fatal.
- `Page` tiene también los métodos `final` `adminRevalidado` (público), `exigirUsuario` y `exigirAdmin` (protegidos): una subclase que declare un método con alguno de esos nombres da un error fatal (500 en esa página). `exigirUsuario` y `exigirAdmin` terminan la petición aunque `redirigir` o `showError`, redefinidos, no lo hagan. También el 405 de `delete` y `editfield` de `AdminHelper` termina aunque `showError` esté redefinido sin terminar.
- Un `Page` propio en `cfw/specific/class/` copiado de una versión anterior no tiene `adminRevalidado`, y eso da un error fatal en todas las páginas de `AdminHelper` y de `PaginaAdmin`: parte del `Page` nuevo.
- `AdminHelper::editfield` valida y escribe como `AdminHelper::edit`, con `update` del ORM y solo la columna editada:
  - Solo admite las columnas que devuelve el método `protected` nuevo `camposEditables($orm)`: las de `campos_form` que están en `$campos`, salvo `id`, la clave primaria, `pass` y los tipos `hidden`, `img`, `file`, `password`, `password_cifrado`, `cabecera`, `checkbox`, `checkbox_dinamico`, `select_desactivado` y `select_dinamico_desactivado`. Un método con ese nombre en una subclase lo redefine; si lo declaras `public`, se podrá llamar por URL. Un ORM sin `campos_form` deja `editfield` sin columnas.
  - Los errores pasan de 200 con «Hubo un error» a 400 con el motivo (por ejemplo, el email repetido de `Usuarios`). Las respuestas 200 y 400 van con `Content-Type: text/plain`, con el texto escapado como antes. Con un método distinto de POST responde 405.
  - Ya no se puede vaciar una celda, y las columnas de `checkbox_singles` solo admiten `0` o `1`.
  - Una subclase con validaciones propias en `edit()` debe redefinir también `editfield`. Puede usar el método `protected` nuevo `rechazarEdicion($s_texto)`, que responde 400 con el texto escapado y termina. Un método con ese nombre en una subclase lo redefine (con una firma o una visibilidad incompatibles da un error fatal, y si lo declaras `public` se podrá llamar por URL); `editfield` termina tras llamarlo aunque la redefinición no lo haga.
- `login::init()` ya no recibe un mensaje (antes `init($s_mensaje = "")`). Para llegar al login con un aviso, guarda con `guardarFlash` la clave de `textos.ini` del aviso, no el texto, y usa `redirigir`, como hace `cuenta`.
- `Orm` solo acepta ids formados por dígitos en `getById`, `getActivoById`, `delete`, `update`, `updateField` y los demás métodos que reciben un id: con otro valor devuelven `false` y añaden un error a `errores`. `update` y `updateField` no escriben la clave primaria, y `updateField` solo admite columnas de `$campos`.
- `update`, `updateField`, `limpiarCategorias` y `addCat` de `Orm` solo escriben si la fila existe y tiene `borrado = 0`; si no, devuelven `false` con `text_fila_no_editable`. Antes, un id inexistente daba `true` y una fila borrada se podía editar y, con `borrado` a 0, recuperar, también desde `edit/<id>/save` de `AdminHelper`. Una tabla sin columna `borrado` ya no se puede actualizar con `update` ni `updateField`: añádela (`borrado int(1) NOT NULL DEFAULT '0'`). Ver «Datos» en [DESARROLLO.md](DESARROLLO.md):
  - `limpiarCategorias` y `addCat` buscan la fila principal en `tabla_otra`: un ORM de categorías debe definirla, o no escriben y dejan una línea `CFW:` en el log.
  - `addCat` rechaza las categorías de `tabla` borradas o inexistentes, con `text_categoria_no_disponible`.
  - `AdminHelper::guardarCategorias` devuelve `bool`: `false` si alguna categoría no se guardó, con los motivos del ORM en la propiedad `protected` nueva `$a_errores_categorias`. Si no puede limpiar las categorías anteriores, no añade ninguna. `form` y `edit` lo avisan en rojo con `text_categorias_problema`, en lugar de mostrar solo el aviso verde. Un `guardarCategorias` redefinido que no devuelva `false` no avisa, y una propiedad con ese nombre en una subclase debe ser `protected` o `public`.
  - `edit/<id>` (y `form` con un id) de `AdminHelper` y de `users`, con una fila borrada o inexistente, ya no pinta un formulario vacío que enviaba a `form/save` y creaba otra fila: muestra el aviso, `text_elemento_no_encontrado` (`text_usuario_no_encontrado` en `users`) y un enlace a la lista. Pasa también tras guardar `borrado` a 1 en la edición, y al abrir la edición de un id borrado o inexistente.
  - `filaEditable` es un método `protected` nuevo de `Orm`: un método con el mismo nombre en un ORM propio lo redefine, y con una firma o una visibilidad incompatibles da un error fatal.
- En `AdminHelper::form`, una subida fallida deshace el alta con un `DELETE` físico (`Orm::eliminarFila`), y `guardarSubidas` devuelve `bool`. Primero borra la fila y después los archivos ya guardados: un archivo que no se pueda borrar deja una línea `CFW:` en el log, y si no se puede borrar la fila se conservan los archivos. Si la tabla no devuelve el id del alta (sin `AUTO_INCREMENT`), la fila se crea pero no se guardan sus subidas ni sus categorías, con el aviso `text_alta_sin_id` y una línea `CFW:` en el log; antes se usaba el id de la URL, que podía ser de otra fila. `hayAsociados`, `archivoEnviado` y `extensionPorContenido` son métodos `protected` nuevos: un método con el mismo nombre en una subclase los redefine.
- `PaginaAdmin` es una interfaz nueva: una clase propia con ese nombre da un error fatal.
- La etiqueta del listado de `AdminHelper` es `nombre`, si está vacío `name` y si no `email`, para cualquier slug. Antes, con el slug `users`, era siempre el `email`: un `AdminHelper` con ese slug y filas con `nombre` o `name` deja de mostrarlo.
- `pass_valida` y `pass_hash` son funciones globales nuevas: no pueden repetirse en `cfw/specific/utils/functions.php`.
- `t()` es global: una función `t` en `cfw/specific/utils/functions.php` da un fatal.
- `cuenta` muestra «Ya existe un usuario con ese e-mail.» si el email nuevo es de otra cuenta, en lugar del error genérico.
- `Usuarios::emailRepetido` reconoce el error 1062 tanto en el índice `email` como en `email_activo`.
- `intentos_carpeta`, `intentos_archivo`, `intentos_abrir`, `intentos_bloqueado`, `intentos_fallo`, `intentos_limpiar` e `intentos_purgar` son funciones globales nuevas: no pueden repetirse en `cfw/specific/utils/functions.php`, porque PHP da un error fatal.
- `select_desactivado` pasa de pintarse como un texto editable que se guardaba a un select deshabilitado con el valor actual, que no se envía ni se guarda.
- `AdminHelper::form` y `AdminHelper::edit` ignoran lo enviado para los campos de tipo `select_desactivado` y `select_dinamico_desactivado` (buscados por su `name` y por su clave en `campos_form`; por la clave, salvo que otro campo no desactivado la use como `name`), también si un JS propio reactiva el select. En el alta, esa columna toma el valor por defecto de la BD. Para que el formulario guarde la columna, usa otro tipo.
- El listado de `AdminHelper` se pagina: un `$paginacion` ya definido en una subclase empieza a aplicarse. Sin él, cada página tiene `db.limit` filas, y lo que antes no se mostraba por pasar de `db.limit` queda en las páginas siguientes. `admin/<slug>/pag/<n>` muestra solo esa página (ver «Administración con `AdminHelper`» en [DESARROLLO.md](DESARROLLO.md)).
- El listado de `users` también se pagina con `$paginacion`, declarada en `users`: una subclase que la redefina debe declararla `protected`. `users` tiene el método público nuevo `pag` (`admin/users/pag/<n>`) y el `protected` nuevo `listado`, al que llama `init()`. Un método con alguno de esos nombres en una subclase lo redefine, y con una firma o una visibilidad incompatibles da un error fatal.
- `Page` tiene los métodos `protected` nuevos `datosPaginacion` y `enlacesPaginacion`, y `AdminHelper` el `protected` nuevo `quitarDesactivados`, con la misma advertencia.
- `Orm::getAllPaginado` ordena por `id`. La página y las filas por página solo se toman si son dígitos (o un `float` entero); otro valor cuenta como 1. Una página que desbordaría el `LIMIT` devuelve `false` sin consultar, en lugar de un error de SQL.
- Los listados de `AdminHelper::init` y `users::listado` usan `getTotal()` y `getAllPaginado()` en lugar de `getAll()`, y se ordenan por `id`. Un `getAll()` redefinido en un ORM propio para filtrar u ordenar deja de aplicarse en el listado: traslada ese filtro u orden a `getTotal()` y `getAllPaginado()`.
- `Form::getInput` admite un 8.º parámetro opcional, `$id`, que por defecto es el `name`. Un `Form` propio en `cfw/specific/class/` no recibe este cambio ni los ids nuevos.
- Ids nuevos: cada opción de `radio`, `checkbox` y `checkbox_dinamico` pasa de `id="<name>"`, repetido, a `id="<name>_<n>"` (`n` desde 1, en el orden de `opciones`); las casillas del listado de `AdminHelper`, de `elementos[]` a `elemento_<id>`; y el selector de idioma de `cuenta`, de `fk_idioma` a `fk_idioma_<código>`. Los `name` no cambian. Un JS o CSS propio que buscara `#<name>` de un radio o una casilla debe usar el id nuevo o `[name=…]`. `checkbox_single` conserva `id="<name>"`.
- `AdminHelper::form` guarda las opciones de `select_dinamico` y `select_dinamico_desactivado`, y lee `value` y marca `checked` de `checkbox_single`, en la clave del campo en `campos_form`, no en una entrada con su `name`. Si la clave y el `name` no coinciden, el formulario ya no pinta la fila separadora vacía de más que salía antes, y la casilla de `checkbox_single` sale marcada cuando el valor guardado coincide con su `value`.

### Plantillas y portada
- Las plantillas ya no admiten PHP incrustado por `eval`.
- Los valores con `'` se guardan como `&#039;`. Escapa la salida con `escapar()`, que no codifica dos veces.
- La portada usa `portada.tpl` y `portada.ini`: `titulo` y `mensaje` de `main.ini` ya no se muestran en ella, y `/admin/main/init/main` da 404. `main.tpl` sigue en uso para `AdminHelper::delete`. Para personalizar la portada, usa `cfw/specific/lang/<código>/portada.ini` o `cfw/specific/templates/portada.tpl`. Una `main` propia en `cfw/specific/pages/main.php` debe declarar su lista de plantillas con `portada` (ver «Páginas, clases y funciones»). Un sitio derivado debe sustituir esta portada en producción, porque publica el framework y sus versiones.
- El formulario de datos de `cuenta` pasa a `web/cuenta_usuario.tpl`, con `data-cifrar` y sin `name` en las contraseñas. `web/cuenta.tpl` lo pinta con `{aviso_cifrado}` y `{form_usuario}`. Si el sitio tiene su propia copia de `web/cuenta.tpl` en `cfw/specific/templates/`, actualízala: con la antigua no se ve el formulario nuevo, o las contraseñas se envían en claro y el servidor responde «formulario caducado».
- Plantilla nueva `paginacion.tpl`, con los enlaces de paginación de los listados de `AdminHelper` y `users`.
- Plantillas de `AdminHelper` (ver «Administración con `AdminHelper`» en [DESARROLLO.md](DESARROLLO.md)):
  - `ver/<id>` usa `template_single` aunque no exista `<slug>.tpl`. Antes, sin `<slug>.tpl`, pintaba `element.tpl`.
  - Si la plantilla de `template_single` no existe, usa `element.tpl` en lugar de responder 404.
  - `template`, `template_listado` y `template_single` escritos con `.tpl` ya se encuentran; antes caían en `elements.tpl` o `element.tpl`.
  - Un nombre no válido (por ejemplo, con `..`) da la plantilla por defecto en lugar de 404.
- `html/select.tpl` pone `id` y `name` entre comillas, y `Form` los escapa. Actualiza las copias de `cfw/specific/templates/html/select.tpl`: sin comillas, un `name` con caracteres especiales se pinta mal.
- `html/input.tpl` usa `{id}` en lugar de `{name}` para el `id`, y el texto de los radios y casillas va en la plantilla nueva `html/label.tpl` (`<label for>`). Actualiza las copias de `html/input.tpl`.
- `menu.tpl` y `menuadmin.tpl` cambian el enlace fijo a «Login» por `{enlace_sesion}`, que `get_menu` rellena con «Login» o con el botón «Salir». Una copia propia sigue mostrando «Login» con la sesión abierta.
- En `listado_pro.tpl`, la columna de la casilla pasa de 10 a 30 px y la de ID de 30 a 70 px, para que la casilla no tape el número y un id largo no se parta. Actualiza las copias propias.

### Aspecto
- `cascara.tpl` lleva `<!DOCTYPE html>`, así que el sitio pasa de modo quirks a modo estándar y cambia el pintado de tablas y alturas de línea. También añade `meta viewport`, `link rel="icon"` a `img/favicon.svg`, el nombre de la cabecera enlazado a la portada y `css/style.css?v=3`. Actualiza las copias de `cfw/specific/templates/cascara.tpl`.
- `css/style.css` se reescribe. La regla `body` pasa de `tablas.css` a `style.css`, y `tablas.css` se carga con `?v=2`. Fusiona los cambios en el CSS propio del sitio. Clases nuevas: `mensaje`, `mensaje_Red`, `mensaje_Green`, `mensaje_Blue`, `form_cuenta`, `form_salir` y `acciones`. Reglas nuevas `select:disabled`, que atenúa los selects desactivados, y `#menu .form_salir`, que da al botón «Salir» del menú aspecto de enlace.
- Plantillas:
  - `usuarios_admin.tpl` y `errores.tpl` pierden las clases `panel-title`, `fondo_claro` y `panel-body`.
  - `usuarios.tpl` y `elements.tpl` usan `h1` para el título.
  - `{content}` y `{error}` van dentro de un `div`, no de un `p`. Los errores de `ErrorCFW` salen en `<p>`, no en `<h2>`.
  - El cierre de sesión pasa al menú, con la plantilla nueva `html/form_salir.tpl` (POST a `login/logout` con `csrf`). `usuarios.tpl` y `usuarios_admin.tpl` ya no tienen botón «Salir» en el cuerpo, y `usuarios.tpl` pierde el `<br/>`, el `<hr/>` y el `div.acciones` finales. `Page::show` sigue rellenando `{boton_salir}` con sesión, salvo que la plantilla ya lo defina, para las plantillas propias. Una copia en `cfw/specific/templates/` de esas plantillas, o de otra que pinte `{boton_salir}`, muestra el botón dos veces (en el cuerpo y en el menú): quítalo de la copia. `text_logout` deja de usarse en `general`.
  - `html/p_color.tpl` añade las clases `mensaje mensaje_{color}`.
  - `web/cuenta_usuario.tpl` ya no lleva estilos en línea.
  - Actualiza las copias de estas plantillas en `cfw/specific/templates/`.
- `get_menu` marca como activos Cuenta, Usuarios e Idiomas, y `/login` marca «Login» en lugar de «Inicio». Con sesión, el menú muestra «Salir» en lugar de «Login».
- Cada página de `general` pone su `page_title`.
- Textos: `text_enlace_admin`, `text_enlace_lista`, `text_enlace_nuevo`, `text_salir` y `text_crear_usuario` empiezan con mayúscula, y `text_titulo_form` pasa a «Formulario de {titulo}», lo que cambia también los títulos de los formularios de `AdminHelper`. Hay una clave nueva, `text_titulo_form_usuarios`. En español, `text_confirmar_borrado` empieza con «¿» y `text_si` pasa a «Sí». `text_cuenta_titulo`, `text_cuenta_guardar` y `text_usuario_desconocido` pasan a mayúscula solo inicial: «Cuenta personal», «Guardar datos personales» y «Usuario desconocido» (en inglés, «Personal account», «Save personal details» y «Unknown user»). `text_campo_tipo` pasa a «Tipo de usuario» (en inglés, «User type»). El título de `/admin/cuenta` cambia también en `<title>`. Las claves de `cfw/specific/lang/` siguen ganando.
- `users` tiene `$titulo_form` para el título del formulario: una subclase que cambie `$titulo` debe cambiarlo también.
- `img/favicon.svg` es el icono de CFW: cada sitio pone el suyo.

### Idiomas
- Los `.ini` de idioma se fusionan por clave: `general` y `specific` de `lang_default`, y encima los del idioma actual. Un `specific/lang/<default>` ya no gana a `general/lang/<idioma>` entero, solo aporta sus claves. `text_error_usuario_mail` pasa a `textos.ini`.
- Desaparecen `get_array_text` y `cfw/general/lang/es/menu.php`. Ya no se carga PHP desde `lang/`: un `menu.php` propio deja de aplicarse. El menú usa `{text_menu_inicio}` y `{text_menu_login}`. Si el sitio cambió `menu.php`, el merge da un conflicto «modify/delete»: bórralo con `git rm`.
- CFW deja de traer `cfw/specific/lang/{es,en}/main.ini`: `titulo` y `mensaje` están en `cfw/general/lang/<código>/main.ini`, con los mismos valores, y en `cfw/specific/` CFW solo trae los `index.php` de protección.
  - Si el sitio no cambió esos ficheros, el merge los borra sin que cambie nada.
  - Si los cambió, el merge da un conflicto «modify/delete». Para conservarlos, `git add` del fichero; si no aportan nada, `git rm`.
- Los `.ini` de idioma se leen con `INI_SCANNER_RAW`. Un fichero con una comilla sin cerrar o una línea sin clave se ignora y deja una línea `CFW:` en el log.
- `titulo` y `singular` de las subclases de `AdminHelper` no se traducen solos: los define cada sitio.
- `data/idiomas.ini` ya no se lee si es un enlace (por ejemplo, a una carpeta compartida de despliegue): el multiidioma se desactiva, `/admin/idiomas` muestra el aviso y queda una línea `CFW:` en el log en cada petición. Copia su contenido a un archivo normal o vuelve a guardar desde `/admin/idiomas` (ver «Multiidioma» en «Despliegue» de [INSTALACION.md](INSTALACION.md)).
- Claves nuevas en `textos.ini`: `text_paginacion_primera`, `text_paginacion_anterior`, `text_paginacion_siguiente`, `text_paginacion_ultima` y `text_paginacion_pagina` (con `{pagina}` y `{paginas}`). CFW las trae en es y en: añádelas en los idiomas propios.
- Claves nuevas en `textos.ini`: `text_fila_no_editable`, `text_categoria_no_disponible` y `text_categorias_problema` (con `{error}`). CFW las trae en es y en: añádelas en los idiomas propios.

### Contraseñas
- **Migración desde md5.** Los md5 existentes se aceptan una vez y se convierten a `password_hash` en el primer login correcto, sin que nadie cambie su contraseña. Las cuentas que nunca inicien sesión siguen en md5. El hash también se renueva si cambia el coste de `PASSWORD_DEFAULT`, por ejemplo al cambiar de versión de PHP.
- `comprobarUserPass` recibe la contraseña real, no su md5.
- `AdminHelper::editfield` ya no admite el campo `pass`.
- El login ya no envía el md5 de la contraseña, sino la contraseña cifrada (ver «Contraseñas» en [README.md](README.md)). Se elimina `js/js-md5-0.9.2.min.js`: si un proyecto la usa en `specific`, debe conservar su propia copia.
- La clave privada del cifrado se rechaza también si otros pueden leerla (por ejemplo 0644): los formularios muestran el aviso hasta corregirla. Hazlo desde la raíz del sitio como el usuario de PHP, que es su propietario, y no como root (ver «Órdenes como Apache» en «Carpeta `data` y permisos» de [INSTALACION.md](INSTALACION.md)): `sudo -u www-data chmod o-r data/clave_privada.pem`, o la ruta de `cifrado.clave_privada` si la usas. Si falla con «Operation not permitted», la clave no es de ese usuario: bórrala y genérala de nuevo como Apache (ver «Permisos exigidos» en «Clave privada del cifrado» de [INSTALACION.md](INSTALACION.md)).
- Los contadores del límite de intentos pasan de `sys_get_temp_dir()/cfw_intentos/` a `data/intentos/` (ver «Límite de intentos» en «Despliegue» de [INSTALACION.md](INSTALACION.md)):
  - Los bloqueos en curso se levantan. La carpeta antigua ya no se usa y se puede borrar.
  - Apache necesita poder escribir en `data` para crearla, o hay que crearla a mano, como Apache (ver «Creación» en «Límite de intentos» de [INSTALACION.md](INSTALACION.md)). Si no se puede crear o no es segura, el límite queda desactivado, con una línea `CFW:` en el log en cada intento de login o de guardado en `cuenta` con la contraseña actual.
  - Una carpeta configurada con `intentos.carpeta` dentro de `/tmp`, o de otra carpeta con escritura para todos, no se acepta.
- Los contadores del límite de intentos cambian de nombre: en lugar del md5 de la IP y el email (o del usuario, en `cuenta`), son un HMAC-SHA256 con un secreto derivado de la clave privada del cifrado.
  - Al actualizar se levantan los bloqueos en curso. Los contadores antiguos se ignoran, y la limpieza que se hace de vez en cuando al anotar un fallo los borra como a cualquier contador de más de 15 minutos.
  - Bórralos a mano después de desplegar el código nuevo: con la carpeta en 0750 o 0755 se pueden listar, y su md5 se puede invertir hasta que llegue esa limpieza. Son los archivos normales de la carpeta de los contadores (`data/intentos`, o la de `intentos.carpeta`) cuyo nombre tiene 32 caracteres hexadecimales; los nuevos tienen 64. Con GNU `find`, desde la raíz del sitio y como el usuario de PHP, que es el propietario de la carpeta, y no como root (ver «Órdenes como Apache» en «Carpeta `data` y permisos» de [INSTALACION.md](INSTALACION.md)); cambia la ruta si usas `intentos.carpeta` (`find` no sigue enlaces y `-type f` deja fuera todo lo que no sea un archivo normal):
    ```
    sudo -u www-data find data/intentos -maxdepth 1 -type f -regextype posix-extended -regex '.*/[0-9a-f]{32}' -delete
    ```
  - Rotar la clave privada también pone los contadores a cero. Sin una clave privada válida, el límite queda desactivado, con una línea `CFW:` en el log.

### JavaScript
- El login usa jQuery 4.0.0 (`js/jquery-4.0.0.min.js`); los formularios con contraseña, node-forge 1.4.2 (`js/forge-1.4.2.min.js`) y `js/cifrado.js`; y el listado de `AdminHelper`, Prototype 1.7.3 (`js/prototype-1.7.3.js`) con TableKit 1.3b. Prototype 1.7.3 lleva el aviso CVE-2020-27511 (ReDoS en `stripTags` y `unescapeHTML`), sin parche: no pases datos de usuario a esas funciones.
- `js/tablekit-cfw.js`, nuevo, corrige el patrón de moneda de TableKit (`$ £ ¥ € ¤`), que en `tablekit.js` perdió esos símbolos y ordenaba esas columnas como texto. Lo carga el listado de `AdminHelper` después de `tablekit.js`; una página propia que use TableKit debe cargarlo también, después de `tablekit.js`.
- Se retira jHtmlArea: desaparecen `$jhtmlarea` de `AdminHelper` y la plantilla `elements_html`.
- `main.js` solo conserva `sendLoginForm` y `devolver_mensaje`. Se eliminan `sendRegisterForm`, `comprobar_mail`, `sendAForm`, `muestraError`, `salir`, `sendFormUser` y los textos que usaban.
- `main.js` y `cifrado.js` pintan sus mensajes con las clases `mensaje mensaje_Red`, y se cargan como `main.js?v=3` y `cifrado.js?v=2`. Si el sitio tiene sus propias copias, actualízalas.
- La edición en línea de TableKit del listado de `AdminHelper` sigue desactivada en el núcleo. Si la activas (tabla con clase `editable` y cabeceras sin `noedit` cuyo `id` es la columna), `listado_pro.tpl` añade el `csrf` al POST y, si `TableKit.options.editAjaxURI` sigue en su valor por defecto (`/`), lo dirige a `admin/<slug>/editfield`; un `editAjaxURI` ya configurado se conserva. El `csrf` solo se añade si esa URL es del mismo origen que la página. El `<script>` pasa a `tablekit.js` una copia de las opciones de Ajax, para que no escriba en `TableKit.options.editAjaxOptions`, y, si la respuesta es un error, devuelve a la celda su contenido anterior y muestra un `alert` (con el motivo si es un 400). Un `listado_pro.tpl` propio en `cfw/specific/templates/` debe copiar el segundo `<script>` del nuevo, que usa la variable `error_edicion_json`: sin él, la respuesta es 403.

### Claves nuevas de `config/config.ini`
Ninguna: `config/config.ini` llega con CFW, con todas las claves de esta versión, y el sitio solo cambia sus valores (ver «Archivos que revisar a mano»). Respecto al `cfw/config.ini` de 1.x, desaparecen `langs` y `page.404`, que nunca se usó: si se deja, se ignora, y si el código propio la leía, el sitio la mantiene en su `config/config.ini`.
