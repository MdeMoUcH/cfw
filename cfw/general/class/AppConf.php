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
	//Claves con un valor seguro: si faltan en config/config.ini y en config/config-servidor.ini, se usan sin aviso.
	private static $a_defecto = Array(
		"desarrollo" => false,
		"charset" => "utf-8",
		"name" => "CFW",
		"lang_default" => "es",
		"description" => "",
		"keywords" => "",
		"analytics" => "",
		"db.limit" => 1000,
		"class.default" => "main"
	);
	
	function __construct()
	{	
		$this->errores = Array();
		
		if(self::$a_config === null)
		{
			//Capas: valores por defecto del código, config/config.ini (sitio) y config/config-servidor.ini (servidor); cada una pisa a la anterior clave a clave.
			$s_raiz = dirname(__DIR__, 3)."/";
			$a_config = array_replace(self::$a_defecto, self::leerIni($s_raiz."config/config.ini", true), self::leerIni($s_raiz."config/config-servidor.ini", false));
			
			//Sin valor por defecto: se avisa solo con el nombre, nunca con valores.
			$a_faltan = Array();
			foreach(Array("urlbase", "email", "db.host", "db.user", "db.pass", "db.name") as $s_clave)
			{
				if(!array_key_exists($s_clave, $a_config))
				{
					$a_faltan[] = $s_clave;
					$a_config[$s_clave] = "";
				}//if
			}//foreach
			if($a_faltan)
			{
				error_log("CFW: faltan claves de configuración sin valor por defecto (config/config.ini o config/config-servidor.ini): ".implode(", ", $a_faltan));
			}//if
			
			//Ubicaciones de 1.x (cfw/config.ini) y de versiones de desarrollo de 2.0.0 (el resto): no se leen ni se mueven.
			$a_antiguas = Array();
			foreach(Array("cfw/config.ini", "cfw/specific/config.ini", "cfw/servidor.ini", "cfw/data/config.ini") as $s_fichero)
			{
				if(file_exists($s_raiz.$s_fichero) || is_link($s_raiz.$s_fichero))
				{
					$a_antiguas[] = $s_fichero;
				}//if
			}//foreach
			if(file_exists($s_raiz."cfw/data") || is_link($s_raiz."cfw/data"))
			{
				$a_contenido = @scandir($s_raiz."cfw/data");
				if(!is_array($a_contenido) || array_diff($a_contenido, Array(".", "..", ".htaccess")))
				{
					$a_antiguas[] = "cfw/data";
				}//if
			}//if
			if($a_antiguas)
			{
				error_log("CFW: ubicaciones anteriores a 2.0.0 que ya no se leen: ".implode(", ", $a_antiguas)."; ver MIGRACION.md (2.0.0)");
			}//if
			
			//Apache solo debe leer config/.
			$a_escribibles = Array();
			foreach(Array("config", "config/config.ini", "config/config-servidor.ini", "config/.htaccess") as $s_fichero)
			{
				if(file_exists($s_raiz.$s_fichero) && is_writable($s_raiz.$s_fichero))
				{
					$a_escribibles[] = $s_fichero;
				}//if
			}//foreach
			if($a_escribibles)
			{
				error_log("CFW: el usuario de PHP puede modificar la configuración: ".implode(", ", $a_escribibles));
			}//if
			
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
