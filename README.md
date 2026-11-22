# cfw
Framework PHP

## Qué es
CFW es un framework MVC en PHP que da una base con opciones básicas: portada, login, cuenta, gestión de usuarios e idiomas. Cada proyecto se construye encima sin modificar esa base.

- **`cfw/general/`** es el funcionamiento base de CFW.
- **`cfw/specific/`** es la parte de cada proyecto. En este repositorio solo lleva los `index.php` de protección, y lo que pongas ahí tiene prioridad sobre `general`.

La versión de CFW está en `cfw/general/VERSION`. La numeración se explica en «Numeración» de [MIGRACION.md](MIGRACION.md).

Las piezas MVC son:
- **Controlador:** las clases de `pages/`, que heredan de `Page`.
- **Modelo:** las clases de `orm/`, que heredan de `Orm`.
- **Vista:** las plantillas `.tpl` de `templates/`, con `Template`. Cómo se rellenan está en «Plantillas» de [DESARROLLO.md](DESARROLLO.md).

## Documentación
- [INSTALACION.md](INSTALACION.md): requisitos, cómo crear un sitio desde una versión de CFW, puesta en marcha, protección de `cfw/`, `config/` y `data/` con `.htaccess`, permisos de `config/` y `data/`, clave privada del cifrado, despliegue y cómo actualizar un sitio a otra versión.
- [MIGRACION.md](MIGRACION.md): regla de numeración y qué revisar al actualizar un sitio ya construido con CFW, con un apartado por versión.
- [DESARROLLO.md](DESARROLLO.md): cómo se construye un sitio sobre CFW: dónde va cada cosa, páginas, plantillas, textos, datos, `AdminHelper`, sesión y contraseñas, configuración, estilo y trampas frecuentes.

## Enrutamiento
Una petición se traduce en clase, método y hasta dos argumentos:

```
/admin/clase/metodo/arg1/arg2  ->  index.php?uno=clase&dos=metodo&tres=arg1&cuatro=arg2
```

1. `index.php` busca `cfw/specific/pages/<clase>.php` y, si no existe, `cfw/general/pages/<clase>.php`; si no hay ninguno, da 404. Si no se indica clase, usa `class.default` de la configuración. El archivo se exige también para `class.default` y desde la línea de órdenes (`php index.php <clase> [método] [arg1] [arg2]`).
2. El fichero debe definir una clase con el mismo nombre, en minúsculas, números y `_`, que herede de `Page` y no sea abstracta.
3. Sin método se ejecuta `init()`. Con método, se ejecuta `$page->metodo($arg1, $arg2)`.
4. Si algo falla, responde 404 con `ErrorCFW`. Los detalles solo se muestran con `desarrollo = true`.

Son accesibles por URL los métodos públicos de la página y de sus clases intermedias (como `AdminHelper`), no los de `AppConf`, `App` ni `Page`, ni los que se llaman como uno de `Page`, ni los estáticos ni los que empiezan por `__` (ver «Páginas» en [DESARROLLO.md](DESARROLLO.md)). Los POST exigen un token CSRF válido.

El `.htaccess` raíz solo define las rutas `/admin/...`, `/login` y `/login/logout` (con o sin barra final), además de `/404/`, que pinta la página de error. Las rutas `/admin/...` admiten de uno a cuatro segmentos no vacíos, con o sin barra final; con más dan 404. Cualquier ruta con `//` (un segmento vacío) da 404, salvo lo que ya da 403 (`cfw/`, `config/`, `data/`, los archivos ocultos...). Otra URL pública, como `/contacto`, necesita su propia regla anclada en ese fichero (`^contacto/?$`) o usar `index.php?uno=contacto`.

## Qué se puede redefinir en `specific`

| Carpeta | Efecto |
|---|---|
| `pages/` | Sustituye a la página de `general` con el mismo nombre, o añade una nueva. La que sustituye a una de `general` hereda de su base `<página>CFW` (`mainCFW`, `usersCFW`…), que no se sustituye, o se copia entera. Una página nueva hereda de `Page` o de `AdminHelper`. |
| `class/` | Sustituye a la clase de `general` con el mismo nombre. La clase propia hereda de su base `…CFW` (`PageCFW`, `FormCFW`…), que no se sustituye, o se copia entera. |
| `orm/` | Sustituye a `Usuarios` o `DinamicOrm`. El ORM propio hereda de `UsuariosCFW` o `DinamicOrmCFW`, que no se sustituyen, o se copia entero. |
| `templates/` | Sustituye a la plantilla con la misma ruta. |
| `lang/<código>/` | Se fusiona clave a clave con los textos de `general`, con prioridad sobre ellos dentro del mismo idioma. |
| `utils/functions.php` | Se carga además del de `general`. |

En `templates/` la sustitución es del fichero entero; en `pages/`, `class/` y `orm/`, la clase propia redefine solo lo que cambia si hereda de su base. No hay carga automática de clases nuevas: ver «Dónde va cada cosa» en [DESARROLLO.md](DESARROLLO.md).

## Qué queda fuera de `specific`
Cada proyecto también adapta estos ficheros, que no están en `specific`: `config/config.ini`, el `.htaccess` raíz, `db.sql`, `css/`, `js/` e `img/`. Al actualizar CFW en un proyecto hay que revisarlos a mano (ver [MIGRACION.md](MIGRACION.md)). `data/`, en la raíz, guarda lo que escribe la aplicación; su contenido no se versiona, salvo su `.htaccess`. La configuración del servidor, `config/config-servidor.ini`, la escribe la persona, va en `config/`, donde Apache solo puede leer, y tampoco se versiona.

La configuración va en dos archivos de `config/`, y el segundo pisa al primero clave a clave: `config/config.ini` lleva los valores de CFW y del sitio que son iguales en todos los servidores, lo edita el sitio y se versiona con él; y `config/config-servidor.ini` es lo de cada servidor y lo secreto (base de datos, `urlbase`, `desarrollo`...), es opcional, no se versiona y Apache solo lo lee. Las claves que falten en ambos toman un valor por defecto del código o, si no lo tienen, se avisan en el log. Ver «Puesta en marcha» en [INSTALACION.md](INSTALACION.md).

## Idiomas
Los textos están en `cfw/general/lang/<código>/` (`textos.ini` y un `.ini` por plantilla). Los de `cfw/specific/lang/<código>/` tienen prioridad, clave a clave.

La opción de multiidioma se gestiona en `/admin/idiomas` y se guarda en `data/idiomas.ini`, que no se versiona. Con ella activa, cada usuario elige su idioma en `cuenta` y los visitantes anónimos reciben el de `Accept-Language`, entre los activos. Si no, todo sale en `lang_default`, que siempre está activo.

Antes de usarla, Apache necesita permiso de escritura sobre `data/`: ver «Carpeta `data` y permisos» en [INSTALACION.md](INSTALACION.md).

Para añadir un idioma, crea `cfw/general/lang/<código>/textos.ini` (en un sitio, `cfw/specific/lang/<código>/textos.ini`), con un código de dos letras minúsculas y `nombre_idioma`; las claves que falten salen en `lang_default`. Si `lang_default` no tiene un `textos.ini` válido, se usa `es`. Cómo se escriben los textos está en «Textos» de [DESARROLLO.md](DESARROLLO.md).

## Contraseñas
Se guardan con `password_hash` (`PASSWORD_DEFAULT`) y se comprueban con `password_verify`.

**Envío cifrado.** El navegador no envía la contraseña en claro ni en md5:
- `js/cifrado.js` cifra `1|<nonce>|<campo>|<contraseña>` con RSA-OAEP (SHA-1) y la clave pública del servidor, usando node-forge. El servidor la descifra con openssl y comprueba que el nonce es de su sesión, no ha caducado (30 minutos) y no se ha usado.
- Cada formulario pintado lleva un nonce de un solo uso: un POST capturado no se puede reenviar, y un formulario antiguo o enviado dos veces muestra «formulario caducado». Se guardan los 20 nonces más recientes de cada sesión: abrir más formularios hace caducar los más antiguos.
- Los campos son del tipo `password_cifrado` de `Form` (o `data-cifrar="<campo>"` en una plantilla). No tienen `name`: si el cifrado falla, no se envía nada. Cómo se usan en una página está en «Sesión y contraseñas» de [DESARROLLO.md](DESARROLLO.md).
- Sin JavaScript no se puede iniciar sesión, guardar los datos de `cuenta` (también el email, que exige la contraseña actual), cambiar la contraseña ni dar de alta usuarios.
- **Límites:** no sustituye a HTTPS. Sin él, quien controle la red puede modificar el JavaScript o la clave pública que se sirven y capturar la contraseña, o robar la cookie de sesión. Con la sesión de un administrador se pueden cambiar las contraseñas de otros usuarios en `/admin/users` sin reautenticarse.

**Clave privada:** se genera sola en el primer uso. Su ubicación, permisos, rotación y generación a mano están en «Clave privada del cifrado» de [INSTALACION.md](INSTALACION.md).

**Límite:** las contraseñas nuevas no pueden pasar de 72 bytes ni tener caracteres nulos (bcrypt ignora lo que sobra y `password_hash` falla con NUL).

Los md5 de versiones anteriores se convierten a `password_hash` en el primer login correcto: ver «Contraseñas» de «2.0.0 (desde 1.x)» en [MIGRACION.md](MIGRACION.md).

## Seguridad
**Protecciones incluidas:**
- Los POST exigen un token CSRF válido.
- La edición en línea de TableKit del listado de `AdminHelper` está desactivada en el núcleo. Si un sitio la activa (tabla con clase `editable` y cabeceras sin `noedit` cuyo `id` es la columna), `listado_pro.tpl` envía el POST a `admin/<slug>/editfield`, salvo que el sitio haya configurado su propio `TableKit.options.editAjaxURI`, y le añade el `csrf` solo si esa URL es del mismo origen que la página. `editfield` solo acepta POST (con otro método responde 405). Si el valor no pasa la validación, responde 400 con el motivo, la celda recupera su contenido anterior y sale un aviso. Lo mismo si no llega respuesta (estado 0), si responde 401 o si la respuesta viene de una redirección (como al login): la celda conserva su valor, no se pinta nada en ella y sale un aviso. La comprobación se hace también si el sitio pone en `editAjaxOptions` un `on0`, `on200` u otro `on<estado>` propio, que se llama después, y un `onComplete` propio se respeta.
- Son accesibles por URL los métodos públicos de la página y de sus clases intermedias (como `AdminHelper`), no los de `AppConf`, `App` ni `Page` (ver «Enrutamiento» y «Páginas» en [DESARROLLO.md](DESARROLLO.md)).
- La cookie de sesión es HttpOnly y SameSite=Lax, y el id de sesión se regenera al entrar y al salir. Es `Secure` solo si PHP recibe la petición por HTTPS (`$_SERVER["HTTPS"]`).
- `cuenta`, `users`, `idiomas` y las páginas de `AdminHelper` comprueban en la BD, en cada petición, que el usuario sigue activo y, en las de administración, que sigue siendo administrador; si no, cierran la sesión y llevan al login. A una petición con la cabecera `X-Requested-With: XMLHttpRequest` (sin distinguir mayúsculas), como las de Prototype, no la redirigen: responden 401 `text/plain` con el texto `text_sesion_caducada`. Si la BD falla o no responde a tiempo, responden 503. Las páginas propias hacen lo mismo con `exigirUsuario()` o `exigirAdmin()`, que son `final` y no se pueden redefinir (ver «Páginas» en [DESARROLLO.md](DESARROLLO.md)). `index.php` no ejecuta ningún método de una página de `AdminHelper` o que implemente la interfaz `PaginaAdmin` (como `users` e `idiomas`) si en esa petición no se ha comprobado en la BD que el usuario es administrador (`exigirAdmin`): una subclase cuyo constructor no llame a `parent::__construct()` ni a `exigirAdmin()` responde 403 a todos, también a los administradores. Una página propia que declare `implements PaginaAdmin` recibe la misma comprobación. Ese 403 deja una línea `CFW:` en el log solo si quien hace la petición tiene sesión de administrador o si `desarrollo = true`: con `desarrollo = true` en producción, cualquiera puede hacer crecer el log pidiendo la página.
- Contraseñas con `password_hash` y enviadas cifradas (ver «Contraseñas»).
- **Clickjacking:** las páginas no se pueden enmarcar en un iframe, ni desde otra web ni desde el mismo sitio (`X-Frame-Options: DENY` y `frame-ancestors 'none'`). Se envían también `X-Content-Type-Options: nosniff` y `Referrer-Policy: strict-origin-when-cross-origin`. Fuera de PHP (estáticos y errores de Apache) dependen de `mod_headers`: ver «Requisitos» en [INSTALACION.md](INSTALACION.md).
- **Límite de intentos:** el login admite 5 fallos por IP y email; al llegar al límite, ese email no puede entrar desde esa IP. En `cuenta`, la contraseña actual admite 5 fallos por usuario, sin IP; al llegar al límite se cierra la sesión y se vuelve al login con un aviso. Los fallos se acumulan mientras entre uno y otro pasen menos de 15 minutos, y el bloqueo dura 15 minutos desde el último fallo. Los contadores no guardan la IP ni el email: su nombre es un HMAC con un secreto derivado de la clave privada.

**Límites:**
- Usa siempre HTTPS. El framework no lo fuerza, y el cifrado de las contraseñas no lo sustituye.
- Las plantillas no escapan la salida (ver «Plantillas» en [DESARROLLO.md](DESARROLLO.md)).
- Los textos de `lang` se pintan sin escapar: quien pueda escribir en `cfw/general/lang/` puede inyectar código en todo el sitio (ver «Carpeta `data` y permisos» en [INSTALACION.md](INSTALACION.md)).
- Prototype 1.7.3 lleva el aviso CVE-2020-27511 (ReDoS en `stripTags` y `unescapeHTML`), sin parche: no pases datos de usuario a esas funciones.
- El límite del login cuenta por IP y email: no frena a quien prueba un mismo email desde muchas IP, ni a quien prueba muchos emails desde una sola IP.
- Quien robe una sesión puede bloquear `cuenta` de ese usuario durante 15 minutos desde el último fallo, fallando la contraseña actual. Detrás de un proxy todos los visitantes comparten `REMOTE_ADDR` y, por tanto, el contador del login: ver «Despliegue» en [INSTALACION.md](INSTALACION.md).
- Cambiar la contraseña no cierra las demás sesiones del usuario.
- Detrás de un proxy que termina el TLS, PHP no ve HTTPS y la cookie de sesión va sin `Secure`: ver «Despliegue» en [INSTALACION.md](INSTALACION.md).
- No uses `AdminHelper::form` ni `AdminHelper::edit` con el ORM `Usuarios`: guardarían `pass` tal como llega, sin `pass_hash` ni las validaciones de `users`. `AdminHelper::editfield` tampoco aplica las de `users`: solo escribe columnas del formulario (`campos_form`, sin `pass` ni los tipos que no se editan en una celda, como `hidden`, `file` o `password_cifrado`), una por petición y a través de `update` del ORM. Pasa por las validaciones que haga ese `update` (con `Usuarios`, por ejemplo, el email repetido) y por las que `AdminHelper::edit` hace en la clase; no aplica las de `users` ni las que dependan de otras columnas de la fila, y no permite vaciar una celda.
- El constructor de una página de `AdminHelper` o `PaginaAdmin` se ejecuta antes de que `index.php` compruebe el administrador: no debe hacer nada antes de comprobarlo (`parent::__construct()` en `AdminHelper`, `users` e `idiomas`; `exigirAdmin()` en una página propia que herede de `Page`; ver «Páginas» en [DESARROLLO.md](DESARROLLO.md)).
- Esa comprobación protege de olvidos, no de código del sitio que la anule a propósito (con Reflection o copiando entero `Page`, `AdminHelper` o `PaginaAdmin` en `specific/class`).
- El constructor de cualquier página se ejecuta antes de que `index.php` compruebe el token CSRF: una página no debe procesar POST en su constructor (ver «Páginas» en [DESARROLLO.md](DESARROLLO.md)).

## Librerías de terceros
- El login usa jQuery 4.0.0 (`js/jquery-4.0.0.min.js`); los formularios con contraseña, node-forge 1.4.2 (`js/forge-1.4.2.min.js`) y `js/cifrado.js`; y el listado de `AdminHelper`, Prototype 1.7.3 (`js/prototype-1.7.3.js`) con TableKit 1.3b y `js/tablekit-cfw.js`, que corrige sin modificar la librería los tipos `currency` y `number` (importes y números en formato español) y recorta los espacios alrededor del texto de las celdas.
- node-forge 1.4.2 (`js/forge-1.4.2.min.js`): copia sin modificar de `dist/forge.min.js` del paquete npm `node-forge` 1.4.2 (repositorio `digitalbazaar/forge`), sha256 `52b553e747df79daa5056ff5e4dff37094c7d8a71194efe2b153d7c3f12763ff`. Licencia BSD-3-Clause o GPL-2.0, a elegir.
- jQuery y Prototype no deben cargarse en la misma página.

## Licencia
CFW se distribuye con la licencia MIT: ver [LICENSE](LICENSE). Las librerías de `js/` tienen la suya (ver «Librerías de terceros»).
