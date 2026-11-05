<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/


function muere($s_msg = "", $b_objeto = true, $b_die = true){
	//funcion para debuggear y probar.
	if($b_objeto){
		print_r("<pre>");
		print_r($s_msg);
		print_r("</pre>");
	}//if
	else{
		print_r($s_msg);
	}//else
	if($b_die){
		die;
	}//if
}//fun


function ordenarArrayPorClave($a_array,$key = ""){
	$a_array = (array)$a_array;
	if($key != ""){
		$a_aux = array();
		foreach($a_array as $element){
			$a_aux[(is_array($element) && isset($element[$key]) && is_scalar($element[$key])) ? (string)$element[$key] : ""] = $element;
		}
		$a_array = $a_aux;
	}
	
	ksort($a_array);
	
	return $a_array;
}

function method_is_public($class, $method)
{
	$reflection = new ReflectionMethod($class, $method);
	if($reflection->isPublic())
	{
		return true;
	}//if
	else
	{
		return false;
	}//else
}//fun method_is_public


function metodo_despachable($class, $method)
{
	if(strpos($method, "__") === 0 || !method_exists($class, $method) || method_exists("Page", $method))
	{
		return false;
	}//if
	
	$reflection = new ReflectionMethod($class, $method);
	if(!$reflection->isPublic() || $reflection->isStatic() || $reflection->isAbstract()
		|| $reflection->isConstructor() || $reflection->isDestructor())
	{
		return false;
	}//if
	
	return !in_array($reflection->getDeclaringClass()->getName(), array("AppConf", "App", "Page"));
}//fun metodo_despachable


function escapar($s)
{
	if(!is_scalar($s))
	{
		return "";
	}//if
	
	return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8", false);
}//fun escapar


//Traduce una clave de textos.ini. Los valores de $a_params deben llegar ya escapados.
function t($s_clave, $a_params = array())
{
	static $a_avisadas = array();
	
	$a_textos = GestorIdiomas::textos("textos");
	if(!isset($a_textos[$s_clave]))
	{
		if(!isset($a_avisadas[$s_clave]))
		{
			$a_avisadas[$s_clave] = true;
			error_log("CFW: falta el texto de idioma: ".$s_clave);
		}//if
		return $s_clave;
	}//if
	
	$a_cambios = array();
	foreach($a_params as $s_nombre=>$valor)
	{
		$a_cambios["{".$s_nombre."}"] = (string)$valor;
	}//foreach
	
	return strtr($a_textos[$s_clave], $a_cambios);
}//fun t


//Valor seguro para pintar dentro de un <script>.
function json_js($valor)
{
	return json_encode($valor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}//fun json_js


function email_valido($s)
{
	$re = '#^[a-z0-9.!\#$%&\'*+-/=?^_`{|}~]+@([0-9.]+|([^\s]+\.+[a-z]{2,6}))(\.[a-z]{2,6})?$#si';
	
	//Lo que Orm::filtro o la columna utf8 vaciarían o recortarían: UTF-8 inválido, etiquetas, control y 4 bytes.
	$re_guardable = '/^[^<>\x00-\x1F\x7F\x{10000}-\x{10FFFF}]+$/uD';
	
	return is_string($s) && strlen($s) <= 254 && preg_match($re_guardable, $s) === 1 && preg_match($re, $s) === 1;
}//fun email_valido


//password_hash da un fatal con NUL y bcrypt ignora lo que pase de 72 bytes.
function pass_valida($s)
{
	return is_string($s) && $s !== "" && strpos($s, "\0") === false && strlen($s) <= 72;
}//fun pass_valida


function pass_hash($s)
{
	return pass_valida($s) ? password_hash($s, PASSWORD_DEFAULT) : false;
}//fun pass_hash


function get_ip()
{
	return isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "127.0.0.1";
}//fun

function getIP(){
	return get_ip();
}


//Límite de intentos. Login: por IP y email; con $b_ip = false, la clave sola (cuenta, por usuario).
define("CFW_INTENTOS_MAX", 5);
define("CFW_INTENTOS_VENTANA", 900);

//intentos.carpeta de config.ini, si es absoluta; si no, cfw/data/intentos. "" si no es segura (fallo abierto).
//Se comprueba una vez por petición.
function intentos_carpeta()
{
	static $s_carpeta = null;
	if($s_carpeta !== null)
	{
		return $s_carpeta;
	}//if
	
	$s_carpeta = "";
	$b_unix = PHP_OS_FAMILY !== "Windows";
	$s_defecto = dirname(__DIR__, 2)."/data/intentos";
	$s_dir = $s_defecto;
	
	$app = new AppConf();
	if(is_array($app->config) && array_key_exists("intentos.carpeta", $app->config))
	{
		//Sin barra final: lstat seguiría el enlace.
		$s_dir = is_string($app->config["intentos.carpeta"]) ? rtrim(trim($app->config["intentos.carpeta"]), $b_unix ? "/" : "/\\") : "";
		if(preg_match($b_unix ? '#^/#' : '#^[A-Za-z]:[\\\\/]#', $s_dir) !== 1)
		{
			error_log("CFW: intentos.carpeta no es una ruta absoluta (vacía, off/no/false/none o relativa); se usa ".$s_defecto);
			$s_dir = $s_defecto;
		}//if
	}//if
	
	//Mismo criterio que Cifrado::carpetaSegura.
	clearstatcache();
	$a_padre = $b_unix ? @stat(dirname($s_dir)) : false;
	$b_padre = !$a_padre || !($a_padre["mode"] & 0002);
	
	//No recursivo. Si falla, se vuelve a mirar por si otra petición la ha creado a la vez.
	if($b_padre && @lstat($s_dir) === false && @mkdir($s_dir, 0700))
	{
		if($b_unix && !function_exists("posix_geteuid"))
		{
			error_log("CFW: sin la extensión posix no se comprueba el propietario de la carpeta de contadores de intentos");
		}//if
	}//if
	clearstatcache();
	$a_lstat = $b_padre ? @lstat($s_dir) : false;
	
	$s_error = "";
	if(!$b_padre)
	{
		$s_error = "su carpeta padre la pueden escribir todos los usuarios";
	}//if
	elseif(!$a_lstat)
	{
		error_log("CFW: no se pudo crear ".$s_dir);
	}//elseif
	elseif(($a_lstat["mode"] & 0170000) !== 0040000)
	{
		$s_error = "es un enlace o no es una carpeta";
	}//elseif
	elseif($b_unix && function_exists("posix_geteuid") && $a_lstat["uid"] !== ($i_uid = posix_geteuid()))
	{
		$s_error = "no es del usuario del proceso (uid ".$i_uid.")";
	}//elseif
	elseif($b_unix && ($a_lstat["mode"] & 0022))
	{
		$s_error = "la pueden escribir el grupo u otros usuarios";
	}//elseif
	else
	{
		$s_carpeta = $s_dir;
	}//else
	
	if($s_error !== "")
	{
		error_log("CFW: carpeta de contadores de intentos rechazada, ".$s_error.": ".$s_dir);
	}//if
	
	return $s_carpeta;
}//fun intentos_carpeta


function intentos_archivo($s_clave, $b_ip = true)
{
	$s_dir = intentos_carpeta();
	if($s_dir === "")
	{
		return "";
	}//if
	
	//Normaliza como Orm::filtro. Las variantes del cotejamiento (ŭ, ſ) las resuelve login con el email guardado.
	return $s_dir."/".md5(($b_ip ? get_ip()."|" : "").strtolower(trim(strip_tags(trim((string)$s_clave)))));
}//fun intentos_archivo


//Abre un contador sin seguir enlaces: archivo normal, el mismo que la ruta (dev/ino) y, en Unix, sin enlaces duros.
function intentos_abrir($s_archivo, $b_escribir)
{
	$b_unix = PHP_OS_FAMILY !== "Windows";
	$fd = false;
	clearstatcache(true, $s_archivo);
	$a_antes = @lstat($s_archivo);
	
	//"x+" sigue los enlaces rotos: solo se usa si no hay nada en la ruta, y se comprueba después.
	if($a_antes === false && $b_escribir && !($fd = @fopen($s_archivo, "x+")))
	{
		clearstatcache(true, $s_archivo);
		$a_antes = @lstat($s_archivo);
	}//if
	
	if(!$fd && $a_antes === false)
	{
		return false;
	}//if
	
	if(!$fd && ($a_antes["mode"] & 0170000) === 0100000 && !($fd = @fopen($s_archivo, $b_escribir ? "r+" : "r")))
	{
		return false;
	}//if
	
	$a_stat = $fd ? fstat($fd) : false;
	clearstatcache(true, $s_archivo);
	$a_lstat = @lstat($s_archivo);
	if($a_stat && $a_lstat && ($a_stat["mode"] & 0170000) === 0100000 && ($a_lstat["mode"] & 0170000) === 0100000
		&& (!$b_unix || ($a_stat["dev"] === $a_lstat["dev"] && $a_stat["ino"] === $a_lstat["ino"] && $a_stat["nlink"] === 1)))
	{
		return $fd;
	}//if
	
	if($fd)
	{
		fclose($fd);
	}//if
	error_log("CFW: contador de intentos rechazado (enlace, enlace duro o no es un archivo normal) en ".dirname($s_archivo));
	
	return false;
}//fun intentos_abrir


function intentos_bloqueado($s_clave, $b_ip = true)
{
	$s_archivo = intentos_archivo($s_clave, $b_ip);
	$fd = $s_archivo != "" ? intentos_abrir($s_archivo, false) : false;
	if(!$fd)
	{
		return false;
	}//if
	
	$a_stat = fstat($fd);
	$b_bloqueado = (time() - $a_stat["mtime"]) < CFW_INTENTOS_VENTANA
		&& (int)stream_get_contents($fd) >= CFW_INTENTOS_MAX;
	fclose($fd);
	
	return $b_bloqueado;
}//fun intentos_bloqueado


function intentos_fallo($s_clave, $b_ip = true)
{
	$s_archivo = intentos_archivo($s_clave, $b_ip);
	if($s_archivo == "")
	{
		return;
	}//if
	
	$fd = intentos_abrir($s_archivo, true);
	if(!$fd || !flock($fd, LOCK_EX))
	{
		error_log("CFW: no se pudo escribir un contador de intentos en ".dirname($s_archivo));
		return;
	}//if
	
	$a_stat = fstat($fd);
	$i_fallos = 0;
	if((time() - $a_stat["mtime"]) < CFW_INTENTOS_VENTANA)
	{
		$i_fallos = (int)stream_get_contents($fd);
	}//if
	ftruncate($fd, 0);
	rewind($fd);
	fwrite($fd, (string)($i_fallos + 1));
	fflush($fd);
	flock($fd, LOCK_UN);
	fclose($fd);
	
	if(mt_rand(1, 100) == 1)
	{
		foreach(glob(dirname($s_archivo)."/*") as $s_viejo)
		{
			if((time() - filemtime($s_viejo)) >= CFW_INTENTOS_VENTANA)
			{
				@unlink($s_viejo);
			}//if
		}//foreach
	}//if
}//fun intentos_fallo


function intentos_limpiar($s_clave, $b_ip = true)
{
	$s_archivo = intentos_archivo($s_clave, $b_ip);
	if($s_archivo != "" && is_file($s_archivo))
	{
		@unlink($s_archivo);
	}//if
}//fun intentos_limpiar


function filtro_utf($cadena)
{
    //$cadena=utf8_encode($cadena);
    $cadena = trim((string)$cadena);
    $cadena = str_replace("&quot;","'",$cadena);
    $cadena = str_replace("&"," and ",$cadena);

    return $cadena;
}//fun


function filtro_mysql_utf8($cadena)
{
	return htmlentities(strip_tags(mb_convert_encoding(trim((string)$cadena), "ISO-8859-1", "UTF-8")), ENT_COMPAT, "ISO-8859-1");
}//fun filtro_mysql

function filtro_mysql($cadena)
{
	return str_replace("'","\'", htmlentities(strip_tags(trim((string)$cadena)), ENT_COMPAT, "ISO-8859-1"));
	
}//fun filtro_mysql



function quitar_barra($s_texto)
{
    $s_texto = str_replace("/","",(string)$s_texto);
    return $s_texto;
}//fun



function enviarMail($to, $subject, $message)
{
	$app = new AppConf();
	$config = $app->config;

    $headers = "From: ".$config["name"]." <".$config["email"].">\r\n" .
                "BCC: ".$config["name"]." <".$config["email"].">\r\n" .
                "Reply-To: ".$config["name"]." <".$config["email"].">\r\n" .
                "X-Mailer: PHP/" . phpversion();

    return mail((string)$to, (string)$subject, (string)$message, $headers);
}//fun


function format_fecha($fecha){
	
	$a_fecha = explode(' ',(string)$fecha);
	
	return t('text_formato_fecha',array('fecha' => $a_fecha[0], 'hora' => (string)@$a_fecha[1]));
	
}










function filtro_url($cadena)
{
    //$cadena=utf8_encode($cadena);
    $cadena = trim(strtolower((string)$cadena));
    
    $cadena = str_replace("á","a",$cadena);
    $cadena = str_replace("ä","a",$cadena);
    $cadena = str_replace("à","a",$cadena);
    $cadena = str_replace("â","a",$cadena);
    $cadena = str_replace("é","e",$cadena);
    $cadena = str_replace("è","e",$cadena);
    $cadena = str_replace("ë","e",$cadena);
    $cadena = str_replace("ê","e",$cadena);
    $cadena = str_replace("í","i",$cadena);
    $cadena = str_replace("ì","i",$cadena);
    $cadena = str_replace("ï","i",$cadena);
    $cadena = str_replace("î","i",$cadena);
    $cadena = str_replace("ó","o",$cadena);
    $cadena = str_replace("ò","o",$cadena);
    $cadena = str_replace("ö","o",$cadena);
    $cadena = str_replace("ô","o",$cadena);
    $cadena = str_replace("ú","u",$cadena);
    $cadena = str_replace("ù","u",$cadena);
    $cadena = str_replace("ü","u",$cadena);
    $cadena = str_replace("û","u",$cadena);
    
    $cadena = str_replace("&","",$cadena);
    $cadena = str_replace("acute","",$cadena);
    
    $cadena = str_replace(" ","-",$cadena);
    
    $a_caracteres = array(":",";","'","'","^",",","+","(",")","[","]","{","}",">","<","@","#","$","%","&","/","\\","?","¿","!","¡","=","\"");
    
    foreach($a_caracteres as $caracter){
		$cadena = str_replace($caracter,"",$cadena);
	}
    

    return $cadena;
}//fun



function nl2p($texto){
	$texto = nl2br((string)$texto);
	$texto = str_replace("<br />", "&nbsp;</p>\n<p>", $texto);
	$texto = "<p>".$texto."</p>";
	
	return $texto;
}//fun





