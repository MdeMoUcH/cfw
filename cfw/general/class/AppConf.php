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
	
	
	//Un opcional que no existe se salta; uno no válido o que parse_ini_file no lee tal como está escrito se ignora entero, sin mostrar valores en el log.
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
		$a_afectadas = self::comprobarIni($s_fichero, $a_datos);
		if($a_afectadas)
		{
			error_log("CFW: ".$s_fichero." no se ha leído tal como está escrito (comillas, \${ o caracteres especiales); se ignora. Claves afectadas: ".implode(", ", $a_afectadas));
			return Array();
		}//if
		return $a_datos;
	}//fun leerIni
	
	
	//Analizador estricto, línea a línea: calcula lo que el fichero quiere decir y lo compara con lo leído. Devuelve los nombres de las claves (o "línea N") que no coinciden.
	private static function comprobarIni($s_fichero, $a_leido)
	{
		$a_esperado = Array();
		$a_afectadas = Array();
		$s_contenido = @file_get_contents($s_fichero);
		$a_lineas = preg_split('/\r\n|\n|\r/', $s_contenido === false ? "" : $s_contenido);
		$a_lineas[0] = preg_replace('/^\xEF\xBB\xBF/', '', $a_lineas[0]);
		
		foreach($a_lineas as $i_linea => $s_linea)
		{
			$s_linea = trim($s_linea);
			if($s_linea === "" || $s_linea[0] === ";" || preg_match('/^\[[^\]]*\]\s*(;.*)?$/', $s_linea))
			{
				continue;
			}//if
			//Como en parse_ini_file, "#" no es un comentario: con "=" la línea es una clave y sin él se descarta.
			if(!preg_match('/^([^=;"\[]+?)\s*(\[[^\]]*\])?\s*=\s*(.*)$/', $s_linea, $a_partes))
			{
				if($s_linea[0] !== "#")
				{
					$a_afectadas[] = "línea ".($i_linea + 1);
				}//if
				continue;
			}//if
			$s_clave = $a_partes[1];
			$s_resto = $a_partes[3];
			$s_valor = null;
			
			if($s_resto !== "" && $s_resto[0] === "'")
			{
				//Sin escapes: \ es literal y no puede haber ' dentro.
				if(preg_match('/^\'([^\']*)\'\s*(;.*)?$/', $s_resto, $a_valor))
				{
					$s_valor = $a_valor[1];
				}//if
			}//if
			elseif($s_resto !== "" && $s_resto[0] === '"')
			{
				//Solo \", \\ y \$ son escapes; un ${ sin escapar se expandiría.
				$s_valor = "";
				$b_valida = true;
				$b_abierta = true;
				$i_largo = strlen($s_resto);
				for($i = 1; $i < $i_largo; $i++)
				{
					$s_c = $s_resto[$i];
					if($s_c === "\\" && $i + 1 < $i_largo)
					{
						$s_d = $s_resto[++$i];
						$s_valor .= ($s_d === '"' || $s_d === "\\" || $s_d === '$') ? $s_d : "\\".$s_d;
					}//if
					elseif($s_c === '"')
					{
						$b_abierta = false;
						break;
					}//elseif
					else
					{
						if($s_c === '$' && $i + 1 < $i_largo && $s_resto[$i + 1] === "{")
						{
							$b_valida = false;
						}//if
						$s_valor .= $s_c;
					}//else
				}//for
				if(!$b_valida || $b_abierta || !preg_match('/^\s*(;.*)?$/', substr($s_resto, $i + 1)))
				{
					$s_valor = null;
				}//if
			}//elseif
			else
			{
				//Sin comillas: hasta el primer ;, y los booleanos de parse_ini_file.
				$a_valor = explode(";", $s_resto, 2);
				$s_sin = trim($a_valor[0]);
				if(strpbrk($s_sin, "'\"") === false && strpos($s_sin, '${') === false)
				{
					$s_minus = strtolower($s_sin);
					if(in_array($s_minus, Array("true", "on", "yes"), true))
					{
						$s_valor = "1";
					}//if
					elseif(in_array($s_minus, Array("false", "off", "no", "none", "null"), true))
					{
						$s_valor = "";
					}//elseif
					else
					{
						$s_valor = $s_sin;
					}//else
				}//if
			}//else
			
			if($s_valor === null)
			{
				//Lo que sigue puede ser parte de este valor y no debe salir en el log.
				foreach(array_keys($a_esperado) as $s_previa)
				{
					if(!array_key_exists($s_previa, $a_leido) || $a_esperado[$s_previa] !== $a_leido[$s_previa])
					{
						$a_afectadas[] = $s_previa;
					}//if
				}//foreach
				$a_afectadas[] = $s_clave;
				return array_values(array_unique($a_afectadas));
			}//if
			if($a_partes[2] === "")
			{
				$a_esperado[$s_clave] = $s_valor;
			}//if
			else
			{
				if(!isset($a_esperado[$s_clave]) || !is_array($a_esperado[$s_clave]))
				{
					$a_esperado[$s_clave] = Array();
				}//if
				//Como parse_ini_file: sin los espacios iniciales y sin las comillas que lo rodean.
				$s_indice = ltrim(substr($a_partes[2], 1, -1));
				if(preg_match('/^(["\'])([^"\'\\\\]*)\1\s*$/', $s_indice, $a_indice))
				{
					$s_indice = $a_indice[2];
				}//if
				if($s_indice === "")
				{
					$a_esperado[$s_clave][] = $s_valor;
				}//if
				else
				{
					$a_esperado[$s_clave][$s_indice] = $s_valor;
				}//else
			}//else
		}//foreach
		
		foreach(array_keys($a_esperado + $a_leido) as $s_clave)
		{
			if(!array_key_exists($s_clave, $a_esperado) || !array_key_exists($s_clave, $a_leido) || $a_esperado[$s_clave] !== $a_leido[$s_clave])
			{
				$a_afectadas[] = $s_clave;
			}//if
		}//foreach
		return array_values(array_unique($a_afectadas));
	}//fun comprobarIni
}//class
