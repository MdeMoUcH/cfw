<?
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
	if($key != ""){
		$a_aux = array();
		foreach($a_array as $element){
			$a_aux[$element[$key]] = $element;
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


function get_ip()
{
	$realip = '';
	
	if(@$_SERVER["SHELL"] == '/bin/bash' || @$_SERVER["SHELL"] == '/bin/sh'){
		   $realip = '127.0.0.1';
	}elseif($_SERVER){
       if ( @$_SERVER["HTTP_X_FORWARDED_FOR"] ){
           $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
       } elseif ( @$_SERVER["HTTP_CLIENT_IP"] ) {
           $realip = $_SERVER["HTTP_CLIENT_IP"];
       } else {
		   if(@$_SERVER["REMOTE_ADDR"]){
				$realip = @$_SERVER["REMOTE_ADDR"];
		   }elseif(@$_SERVER["SHELL"] == '/bin/bash'){
			   $realip = '127.0.0.1';
		   }
       }
    } else {
       if ( getenv( "HTTP_X_FORWARDED_FOR" ) ) {
           $realip = getenv( "HTTP_X_FORWARDED_FOR" );
       } elseif ( getenv( "HTTP_CLIENT_IP" ) ) {
           $realip = getenv( "HTTP_CLIENT_IP" );
       } else {
           $realip = getenv( "REMOTE_ADDR" );
       }
    }

    return($realip);
}//fun

function getIP(){
	return get_ip();
}


function filtro_utf($cadena)
{
    //$cadena=utf8_encode($cadena);
    $cadena = trim($cadena);
    $cadena = str_replace("&quot;","'",$cadena);
    $cadena = str_replace("&"," and ",$cadena);

    return $cadena;
}//fun


function filtro_mysql_utf8($cadena)
{
	return htmlentities(strip_tags(utf8_decode(trim($cadena))), ENT_COMPAT, "ISO-8859-1");
}//fun filtro_mysql

function filtro_mysql($cadena)
{
	return str_replace("'","\'", htmlentities(strip_tags(trim($cadena)), ENT_COMPAT, "ISO-8859-1"));
	
}//fun filtro_mysql



function quitar_barra($s_texto)
{
    $s_texto = str_replace("/","",$s_texto);
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

    return mail($to, $subject, $message, $headers);
}//fun


function format_fecha($fecha){
	
	$a_fecha = explode(' ',$fecha);
	
	return 'El '.$a_fecha[0].' a las '.$a_fecha[1];
	
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

    $thumb = imagecreatetruecolor($anchura,$altura) or die('Cannot Initialize new GD image stream');
    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $anchura, $altura, $a_datos[0], $a_datos[1]);

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

    imagedestroy($thumb);

    return $b_ok;

}//fun


function filtro_url($cadena)
{
    //$cadena=utf8_encode($cadena);
    $cadena = trim(strtolower($cadena));
    
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
	$texto = nl2br($texto);
	$texto = str_replace("<br />", "&nbsp;</p>\n<p>", $texto);
	$texto = "<p>".$texto."</p>";
	
	return $texto;
}//fun





