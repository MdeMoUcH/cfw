<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 
 * 2010
 * 
 ***************/



class AppConf
{	
	public $config;
	public $errores;
	private static $a_config = null;
	
	function __construct()
	{	
		$this->errores = Array();
		
		if(self::$a_config === null)
		{
			//Capas: CFW, sitio (specific) y servidor (data); cada una pisa a la anterior clave a clave.
			$s_base = dirname(__DIR__, 2)."/";
			$a_config = self::leerIni($s_base."config.ini", true);
			foreach(Array("specific/config.ini", "data/config.ini") as $s_fichero)
			{
				$a_config = array_replace($a_config, self::leerIni($s_base.$s_fichero, false));
			}//foreach
			
			if(!isset($a_config["pathbase"]) || !is_string($a_config["pathbase"]) || trim($a_config["pathbase"]) === "")
			{
				$a_config["pathbase"] = dirname(__DIR__, 3)."/";
			}//if
			foreach(Array("urlbase", "pathbase") as $s_clave)
			{
				if(isset($a_config[$s_clave]) && is_string($a_config[$s_clave]) && $a_config[$s_clave] !== "")
				{
					$s_final = substr($a_config[$s_clave], -1);
					if($s_final !== "/" && !($s_clave === "pathbase" && DIRECTORY_SEPARATOR === "\\" && $s_final === "\\"))
					{
						$a_config[$s_clave] .= "/";
					}//if
				}//if
			}//foreach
			self::$a_config = $a_config;
		}//if
		$this->config = self::$a_config;
		
		if(!empty($this->config["desarrollo"]))
		{
			error_reporting(E_ALL);
		}//if
		else
		{
			error_reporting(0);
		}//else
	}//__construct
	
	
	//Un opcional que no existe se salta; uno no válido se ignora entero, sin mostrar su contenido en el log.
	private static function leerIni($s_fichero, $b_obligatorio)
	{
		if(!$b_obligatorio && !file_exists($s_fichero) && !is_link($s_fichero))
		{
			return Array();
		}//if
		
		$a_datos = is_file($s_fichero) ? @parse_ini_file($s_fichero) : false;
		if(!is_array($a_datos))
		{
			error_log("CFW: ".$s_fichero." no es un .ini válido o no se puede leer; se ignora");
			return Array();
		}//if
		return $a_datos;
	}//fun leerIni
}//class
