<?php
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


function comprobar_csrf($page)
{
	if(@$_SERVER["REQUEST_METHOD"] === "POST" && !$page->session->comprobarCsrf(@$_POST["csrf"]))
	{
		http_response_code(403);
		$page->showError("La petición no es válida. Vuelve a cargar la página e inténtalo de nuevo.");
	}//if
}//fun comprobar_csrf


$errores = array();
if(PHP_SAPI === 'cli'){
	$class = isset($argv[1]) ? $argv[1] : $app->config["class.default"];
	$arg1 = @$argv[2];
	$arg2 = @$argv[3];
	$arg3 = @$argv[4];
}else{
	$class = $app->config["class.default"];
	$arg1 = @$_GET["dos"];
	$arg2 = @$_GET["tres"];
	$arg3 = @$_GET["cuatro"];
}

if(!is_string($class) || !preg_match('/^[a-z0-9_]+$/D', $class))
{
	$errores[] = "La página no es válida.";
}//if

if(@$_GET["uno"] !== null && @$_GET["uno"] !== "")
{
	if(!is_string($_GET["uno"]) || !preg_match('/^[a-z0-9_]+$/D', $_GET["uno"]))
	{
		$errores[] = "La página no es válida.";
	}//if
	elseif(file_exists($app->config["pathbase"]."cfw/specific/pages/".$_GET["uno"].".php") ||
		file_exists($app->config["pathbase"]."cfw/general/pages/".$_GET["uno"].".php"))
	{
		$class = $_GET["uno"];
	}//elseif
	else
	{
		$errores[] = "No se encontró el archivo '".$_GET["uno"]."'.";
	}//else
}//if

foreach(array($arg1, $arg2, $arg3) as $arg)
{
	if($arg !== null && !is_string($arg))
	{
		$errores[] = "La dirección no es válida.";
		break;
	}//if
}//foreach

if(count($errores) < 1 && $arg1 != "" && !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $arg1))
{
	$errores[] = "La dirección no es válida.";
}//if


if(count($errores) < 1)
{
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
			if(metodo_despachable($class, $arg1))
			{
				$page = new $class();
				comprobar_csrf($page);
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
		}//elseif
		else
		{
			$page = new $class();
			comprobar_csrf($page);
			//$page->init($arg1,$arg2);
			$page->init();
			die();
		}//else
	}//if
	else
	{
		$errores[] = "No existe la clase '".$class."'.";
	}//else
}//if




if(!$app->config["desarrollo"])
{
	$errores = array("Sorry bro, 404");
}
http_response_code(404);
$page = new ErrorCFW();
$page->init($errores);


