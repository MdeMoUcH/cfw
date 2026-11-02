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
		$page->showError(t("text_peticion_no_valida"));
	}//if
}//fun comprobar_csrf


//Clave y parámetros: el texto se traduce cuando ya hay sesión.
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
	$errores[] = array("text_pagina_no_valida");
}//if

if(@$_GET["uno"] !== null && @$_GET["uno"] !== "")
{
	if(!is_string($_GET["uno"]) || !preg_match('/^[a-z0-9_]+$/D', $_GET["uno"]))
	{
		$errores[] = array("text_pagina_no_valida");
	}//if
	elseif(file_exists($app->config["pathbase"]."cfw/specific/pages/".$_GET["uno"].".php") ||
		file_exists($app->config["pathbase"]."cfw/general/pages/".$_GET["uno"].".php"))
	{
		$class = $_GET["uno"];
	}//elseif
	else
	{
		$errores[] = array("text_archivo_no_encontrado", array("x" => $_GET["uno"]));
	}//else
}//if

foreach(array($arg1, $arg2, $arg3) as $arg)
{
	if($arg !== null && !is_string($arg))
	{
		$errores[] = array("text_direccion_no_valida");
		break;
	}//if
}//foreach

if(count($errores) < 1 && $arg1 != "" && !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $arg1))
{
	$errores[] = array("text_direccion_no_valida");
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

	if(class_exists($class) && is_subclass_of($class,"Page"))
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
				$errores[] = array("text_metodo_no_publico", array("metodo" => $arg1, "clase" => $class));
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
			$errores[] = array("text_metodo_no_existe", array("metodo" => $arg1, "clase" => $class));
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
		$errores[] = array("text_clase_no_existe", array("clase" => $class));
	}//else
}//if




if(!$app->config["desarrollo"])
{
	$errores = array(array("text_404_produccion"));
}
http_response_code(404);
$page = new ErrorCFW();
$a_textos_error = array();
foreach($errores as $a_error)
{
	$a_textos_error[] = t($a_error[0], isset($a_error[1]) ? $a_error[1] : array());
}//foreach
$page->init($a_textos_error);


