<?
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/


//IMAGENES:


function saveImageProductos($image_data, $s_nombre = "", $anchura = "", $hmax = "")
{
    global $config;

    $b_ok = false;


    if($anchura == "")
    {
        $anchura = $config["img.productos.width"];
    }//if

    if($hmax == "")
    {
        $hmax = $config["img.productos.height"];
    }//if

	if($s_nombre != "")
	{
		$b_ext = true;
	}//if
	else
	{
		$s_nombre = $image_data["name"];
		$b_ext = false;
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


    if($a_datos[2] == 1)
    {
        //header("Content-type: image/gif");
        $b_ok = imagegif($thumb,$config["img.productos.ruta"].$s_nombre);
    }
    if($a_datos[2] == 2)
    {
        //header("Content-type: image/jpeg");
        $b_ok = imagejpeg($thumb,$config["img.productos.ruta"].$s_nombre);
    }//if
    if($a_datos[2] == 3)
    {
        //header("Content-type: image/png");
        $b_ok = imagepng($thumb,$config["img.productos.ruta"].$s_nombre);
    }//if

    imagedestroy($thumb);
    
    

    return $b_ok;

}//fun
