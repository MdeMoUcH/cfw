# cfw
Framework PHP

## Qué es
CFW es un framework MVC en PHP que da una base con opciones básicas: portada, login, cuenta, gestión de usuarios e idiomas. Cada proyecto se construye encima sin modificar esa base.

- **`cfw/general/`** es el funcionamiento base de CFW.
- **`cfw/specific/`** es la parte de cada proyecto. En este repositorio solo está su estructura, y lo que pongas ahí tiene prioridad sobre `general`.

Las piezas MVC son:
- **Controlador:** las clases de `pages/`, que heredan de `Page`.
- **Modelo:** las clases de `orm/`, que heredan de `Orm`.
- **Vista:** las plantillas `.tpl` de `templates/`, con `Template`. En ellas, `{nombre}` toma su valor de `$tpl->vars`, del `.ini` de la plantilla o, si empieza por `text_`, de `textos.ini`, y `{csrf}` da el token CSRF. Lo que no tenga valor queda vacío, salvo un `text_` que falte, que sale con su nombre, y los valores no se vuelven a expandir.

## Documentación
- [INSTALACION.md](INSTALACION.md): requisitos, puesta en marcha, protección de `cfw/` con `.htaccess`, permisos de `cfw/data`, clave privada del cifrado y despliegue.
- [MIGRACION.md](MIGRACION.md): qué revisar al actualizar a esta versión un sitio ya construido con CFW.

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

El `.htaccess` raíz solo define las rutas `/admin/...` y `/login`, además de `/404/`, que pinta la página de error. Otra URL pública, como `/contacto`, necesita su propia regla en ese fichero o usar `index.php?uno=contacto`.

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
Cada proyecto también adapta estos ficheros, que no están en `specific`: `cfw/config.ini`, el `.htaccess` raíz, `db.sql`, `css/`, `js/` e `img/`. Al actualizar CFW en un proyecto hay que revisarlos a mano (ver [MIGRACION.md](MIGRACION.md)). `cfw/data/` guarda lo que escribe la aplicación. Su contenido no se versiona, salvo su `.htaccess`.

## Idiomas
Los textos están en `cfw/general/lang/<código>/` (`textos.ini` y un `.ini` por plantilla). Los de `cfw/specific/lang/<código>/` tienen prioridad, clave a clave.

La opción de multiidioma se gestiona en `/admin/idiomas` y se guarda en `cfw/data/idiomas.ini`, que no se versiona. Con ella activa, cada usuario elige su idioma en `cuenta` y los visitantes anónimos reciben el de `Accept-Language`, entre los activos. Si no, todo sale en `lang_default`, que siempre está activo.

Antes de usarla, Apache necesita permiso de escritura sobre `cfw/data/`: ver «Carpeta `cfw/data` y permisos» en [INSTALACION.md](INSTALACION.md).

Para añadir un idioma, crea `cfw/general/lang/<código>/textos.ini` o `cfw/specific/lang/<código>/textos.ini`, con un código de dos letras minúsculas y `nombre_idioma`; las claves que falten salen en `lang_default`. Si `lang_default` no tiene un `textos.ini` válido, se usa `es`. Los valores van entre comillas dobles y no pueden contener comillas dobles. Si el valor lleva HTML o datos, escápalos antes de pasarlos a `t()`.

## Contraseñas
Se guardan con `password_hash` (`PASSWORD_DEFAULT`) y se comprueban con `password_verify`.

**Envío cifrado.** El navegador no envía la contraseña en claro ni en md5:
- `js/cifrado.js` cifra `1|<nonce>|<campo>|<contraseña>` con RSA-OAEP (SHA-1) y la clave pública del servidor, usando node-forge. El servidor la descifra con openssl y comprueba que el nonce es de su sesión, no ha caducado (30 minutos) y no se ha usado.
- Cada formulario pintado lleva un nonce de un solo uso: un POST capturado no se puede reenviar, y un formulario antiguo o enviado dos veces muestra «formulario caducado». Se guardan los 20 nonces más recientes de cada sesión: abrir más formularios hace caducar los más antiguos.
- Los campos son del tipo `password_cifrado` de `Form` (o `data-cifrar="<campo>"` en una plantilla). No tienen `name`: si el cifrado falla, no se envía nada. En el controlador, `Page::prepararCifrado()` prepara la página y `Page::leerPassCifradas()` devuelve las contraseñas descifradas.
- Sin JavaScript no se puede iniciar sesión, guardar los datos de `cuenta` (también el email, que exige la contraseña actual), cambiar la contraseña ni dar de alta usuarios.
- **Límites:** no sustituye a HTTPS. Sin él, quien controle la red puede modificar el JavaScript o la clave pública que se sirven y capturar la contraseña, o robar la cookie de sesión. Con la sesión de un administrador se pueden cambiar las contraseñas de otros usuarios en `/admin/users` sin reautenticarse.

**Clave privada:** se genera sola en el primer uso. Su ubicación, permisos, rotación y generación a mano están en «Clave privada del cifrado» de [INSTALACION.md](INSTALACION.md).

**Límite:** las contraseñas nuevas no pueden pasar de 72 bytes ni tener caracteres nulos (bcrypt ignora lo que sobra y `password_hash` falla con NUL).

Los md5 de versiones anteriores se convierten a `password_hash` en el primer login correcto: ver «Contraseñas» en [MIGRACION.md](MIGRACION.md).

## Seguridad
**Protecciones incluidas:**
- Los POST exigen un token CSRF válido.
- Solo son accesibles por URL los métodos públicos declarados en la página, no los de `App` ni `Page` (ver «Enrutamiento»).
- La cookie de sesión es HttpOnly y SameSite=Lax, y el id de sesión se regenera al entrar y al salir. Es `Secure` solo si PHP recibe la petición por HTTPS (`$_SERVER["HTTPS"]`).
- `cuenta`, `users`, `idiomas` y las páginas de `AdminHelper` comprueban en la BD, en cada petición, que el usuario sigue activo y, en las de administración, que sigue siendo administrador; si no, cierran la sesión y llevan al login. Si la BD falla, responden 503. Las páginas propias que exijan sesión deben llamar a `$this->exigirUsuario()` o a `$this->exigirAdmin()` en su constructor. Una subclase de `AdminHelper` con constructor propio debe llamar a `parent::__construct()`; si no, no exige administrador.
- Contraseñas con `password_hash` y enviadas cifradas (ver «Contraseñas»).
- **Clickjacking:** las páginas no se pueden enmarcar en un iframe, ni desde otra web ni desde el mismo sitio (`X-Frame-Options: DENY` y `frame-ancestors 'none'`). Se envían también `X-Content-Type-Options: nosniff` y `Referrer-Policy: strict-origin-when-cross-origin`. Fuera de PHP (estáticos y errores de Apache) dependen de `mod_headers`: ver «Requisitos» en [INSTALACION.md](INSTALACION.md).
- **Límite de intentos:** el login admite 5 fallos por IP y email; al llegar al límite, ese email no puede entrar desde esa IP. En `cuenta`, la contraseña actual admite 5 fallos por usuario, sin IP; al llegar al límite se cierra la sesión y se vuelve al login con un aviso. Los fallos se acumulan mientras entre uno y otro pasen menos de 15 minutos, y el bloqueo dura 15 minutos desde el último fallo. Los contadores no guardan la IP ni el email: su nombre es un HMAC con un secreto derivado de la clave privada.

**Límites:**
- Usa siempre HTTPS. El framework no lo fuerza, y el cifrado de las contraseñas no lo sustituye.
- Las plantillas no escapan la salida. Escapa la salida con `escapar()`, que no codifica dos veces.
- Los textos de `lang` se pintan sin escapar: quien pueda escribir en `cfw/general/lang/` puede inyectar código en todo el sitio (ver «Carpeta `cfw/data` y permisos» en [INSTALACION.md](INSTALACION.md)).
- Prototype 1.7.3 lleva el aviso CVE-2020-27511 (ReDoS en `stripTags` y `unescapeHTML`), sin parche: no pases datos de usuario a esas funciones.
- El límite del login cuenta por IP y email: no frena a quien prueba un mismo email desde muchas IP, ni a quien prueba muchos emails desde una sola IP.
- Quien robe una sesión puede bloquear `cuenta` de ese usuario durante 15 minutos desde el último fallo, fallando la contraseña actual. Detrás de un proxy todos los visitantes comparten `REMOTE_ADDR` y, por tanto, el contador del login: ver «Despliegue» en [INSTALACION.md](INSTALACION.md).
- Cambiar la contraseña no cierra las demás sesiones del usuario.
- Detrás de un proxy que termina el TLS, PHP no ve HTTPS y la cookie de sesión va sin `Secure`: ver «Despliegue» en [INSTALACION.md](INSTALACION.md).
- No uses `AdminHelper::form` ni `AdminHelper::edit` con el ORM `Usuarios`: guardarían `pass` tal como llega, sin `pass_hash` ni las validaciones de `users`.

## Librerías de terceros
- El login usa jQuery 4.0.0 (`js/jquery-4.0.0.min.js`); los formularios con contraseña, node-forge 1.4.2 (`js/forge-1.4.2.min.js`) y `js/cifrado.js`; y el listado de `AdminHelper`, Prototype 1.7.3 (`js/prototype-1.7.3.js`) con TableKit 1.3b.
- node-forge 1.4.2 (`js/forge-1.4.2.min.js`): copia sin modificar de `dist/forge.min.js` del paquete npm `node-forge` 1.4.2 (repositorio `digitalbazaar/forge`), sha256 `52b553e747df79daa5056ff5e4dff37094c7d8a71194efe2b153d7c3f12763ff`. Licencia BSD-3-Clause o GPL-2.0, a elegir.
- jQuery y Prototype no deben cargarse en la misma página.
