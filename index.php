<?
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/


require_once("cfw/includes.php");
//die("hello");
$app = new AppConf();

if($app->config["desarrollo"])
{
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}//if
else
{
	error_reporting(0);
}//else


if(@$argv){
	$errores = array();
	$class = @$argv[1];
	$arg1 = @$argv[2];
	$arg2 = @$argv[3];
	$arg3 = @$argv[4];
}else{
	$errores = array();
	$class = $app->config["class.default"];
	$arg1 = @$_GET["dos"];
	$arg2 = @$_GET["tres"];
	$arg3 = @$_GET["cuatro"];
}

if(@$_GET["uno"] != "")
{
	if(file_exists($app->config["pathbase"]."cfw/specific/pages/".$_GET["uno"].".php") ||
		file_exists($app->config["pathbase"]."cfw/general/pages/".$_GET["uno"].".php"))
	{
		$class = $_GET["uno"];
	}//if
	else
	{
		$errores[] = "No se encontró el archivo '".$_GET["uno"]."'.";
	}//else
}//if


if(file_exists($app->config["pathbase"]."cfw/specific/pages/".$class.".php"))
{
	require_once($app->config["pathbase"]."cfw/specific/pages/".$class.".php");
}//if
elseif(file_exists($app->config["pathbase"]."cfw/general/pages/".$class.".php"))
{
	require_once($app->config["pathbase"]."cfw/general/pages/".$class.".php");
}//elseif

if(class_exists($class))
{
	if($arg1 != "" && method_exists($class,$arg1))
	{
		if(method_is_public($class, $arg1))
		{
			$page = new $class();
			$page->$arg1($arg2,$arg3);
			die();
		}//if
		else
		{
			$errores[] = "El método '".$arg1."' de la clase '".$class."' no es público.";
		}//else
	}//if
	elseif($arg1 != "")
	{
		//$page = new $class($arg1);
		/***********************
		 * ÑAPA ESTRATOSFÉRICA *
		 *********************** /
		if($class == "web"){
			$page = new $class();
			$page->init($arg1,$arg2);
		}//if
		/ **NO HAGAN ESTO EN CASA** /
		*/
		$errores[] = "No existe el método '".$arg1."' en la clase '".$class."'.";
		
	}//else
	elseif(count($errores) < 1)
	{
		$page = new $class();
		//$page->init($arg1,$arg2);
		$page->init();
		die();
	}
}//if
else
{
	$errores[] = "No existe la clase '".$class."'.";
}//else




if(!$app->config["desarrollo"])
{
	$errores = array("Sorry bro, 404");
}
$page = new ErrorCFW();
$page->init($errores);


