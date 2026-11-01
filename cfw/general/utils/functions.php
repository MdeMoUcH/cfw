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
			$a_aux[(string)@$element[$key]] = $element;
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


function email_valido($s)
{
	$re = '#^[a-z0-9.!\#$%&\'*+-/=?^_`{|}~]+@([0-9.]+|([^\s]+\.+[a-z]{2,6}))(\.[a-z]{2,6})?$#si';
	
	//Lo que Orm::filtro o la columna utf8 vaciarían o recortarían: UTF-8 inválido, etiquetas, control y 4 bytes.
	$re_guardable = '/^[^<>\x00-\x1F\x7F\x{10000}-\x{10FFFF}]+$/uD';
	
	return is_string($s) && strlen($s) <= 254 && preg_match($re_guardable, $s) === 1 && preg_match($re, $s) === 1;
}//fun email_valido


function get_ip()
{
	return isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "127.0.0.1";
}//fun

function getIP(){
	return get_ip();
}


//Límite de intentos de login por IP y email.
define("CFW_INTENTOS_MAX", 5);
define("CFW_INTENTOS_VENTANA", 900);

function intentos_archivo($s_email)
{
	$s_dir = sys_get_temp_dir()."/cfw_intentos/";
	if(!is_dir($s_dir) && !@mkdir($s_dir, 0700, true) && !is_dir($s_dir))
	{
		error_log("CFW: no se pudo crear ".$s_dir);
		return "";
	}//if
	
	//Normaliza como Orm::filtro. Las variantes del cotejamiento (ŭ, ſ) las resuelve login con el email guardado.
	return $s_dir.md5(get_ip()."|".strtolower(trim(strip_tags(trim((string)$s_email)))));
}//fun intentos_archivo


function intentos_bloqueado($s_email)
{
	$s_archivo = intentos_archivo($s_email);
	if($s_archivo == "" || !is_file($s_archivo))
	{
		return false;
	}//if
	
	clearstatcache(true, $s_archivo);
	return (time() - filemtime($s_archivo)) < CFW_INTENTOS_VENTANA
		&& (int)file_get_contents($s_archivo) >= CFW_INTENTOS_MAX;
}//fun intentos_bloqueado


function intentos_fallo($s_email)
{
	$s_archivo = intentos_archivo($s_email);
	if($s_archivo == "")
	{
		return;
	}//if
	
	$fd = @fopen($s_archivo, "c+");
	if(!$fd || !flock($fd, LOCK_EX))
	{
		error_log("CFW: no se pudo escribir ".$s_archivo);
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


function intentos_limpiar($s_email)
{
	$s_archivo = intentos_archivo($s_email);
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
	
	return 'El '.$a_fecha[0].' a las '.@$a_fecha[1];
	
}










//IMAGENES:


function saveImage($image_data, $s_nombre = "", $anchura = "", $hmax = "", $b_ext = true)
{
	$app = new app();
    $config = $app->config;

    $b_ok = false;


    if($anchura == "")
    {
        $anchura = $config["img.width"];
    }//if

    if($hmax == "")
    {
        $hmax = $config["img.height"];
    }//if

	if($s_nombre == "")
	{
		$s_nombre = $image_data["name"];
		if($b_ext){
			$b_ext = false;
		}
	}else{
		$s_nombre = $s_nombre;
		/*if($b_ext){
			$b_ext = true;
		}else{
			die('que pa..');
		}*/
	}//else

    $s_image = $image_data["tmp_name"];

    $a_datos = getimagesize($s_image);

    if($a_datos[2] == 1)
    {
        $img = @imagecreatefromgif($s_image);
        if($b_ext)
        {
			$s_nombre .= ".gif";
		}//if
    }//if
    if($a_datos[2] == 2)
    {
        $img = @imagecreatefromjpeg($s_image);
        if($b_ext)
        {
			$s_nombre .= ".jpg";
		}//if
    }//if
    if($a_datos[2] == 3)
    {
        $img = @imagecreatefrompng($s_image);
        if($b_ext)
        {
			$s_nombre .= ".png";
		}//if
    }//if

    $ratio = ($a_datos[0] / $anchura);
    $altura = ($a_datos[1] / $ratio);
    if($altura > $hmax)
    {
        $anchura2 = $hmax * $anchura / $altura;
        $altura = $hmax;
        $anchura = $anchura2;
    }//if

    $thumb = imagecreatetruecolor((int)$anchura,(int)$altura) or die('Cannot Initialize new GD image stream');
    imagecopyresampled($thumb, $img, 0, 0, 0, 0, (int)$anchura, (int)$altura, $a_datos[0], $a_datos[1]);

	/*
    //Marca de agua
    $forrest_green = imagecolorallocate($thumb, 34, 139, 34);
    $white = imagecolorallocate($thumb, 255, 255, 255);
    $black = imagecolorallocate($thumb, 0, 0, 0);
    if($anchura <= 180)
    {
        imagestring($thumb, 1, ($anchura-84), ($altura-10),  'lagranm.com', $white);
    }//if
    elseif($anchura <= 360)
    {
        imagestring($thumb, 24, ($anchura-180), ($altura-12),  'lagranm.com', $black);
    }//elseif
    else
    {
        imagestring($thumb, 24, ($anchura-360), ($altura-15),  'lagranm.com', $forrest_green);
    }//else
    //Marca de agua
	*/

    if($a_datos[2] == 1)
    {
        //header("Content-type: image/gif");
        $b_ok = imagegif($thumb,$config["img.ruta"].$s_nombre);
    }
    if($a_datos[2] == 2)
    {
        //header("Content-type: image/jpeg");
        $b_ok = imagejpeg($thumb,$config["img.ruta"].$s_nombre);
    }//if
    if($a_datos[2] == 3)
    {
        //header("Content-type: image/png");
        $b_ok = imagepng($thumb,$config["img.ruta"].$s_nombre);
    }//if

    return $b_ok;

}//fun


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





