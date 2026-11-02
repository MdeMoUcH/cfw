<?php
/***************
 *
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 *
 * 2010
 *
 ***************/


//No se llama Idiomas: PHP no distingue mayúsculas y chocaría con la página idiomas.
class GestorIdiomas
{
	private static $a_ini = array();
	private static $a_textos = array();
	private static $a_disponibles = null;
	private static $a_opciones = null;
	private static $s_default = null;
	private static $a_navegador = array();
	private static $b_vary_enviado = false;


	private static function rutaBase()
	{
		return dirname(__DIR__, 2)."/";
	}//fun rutaBase


	private static function rutaOpciones()
	{
		return self::rutaBase()."datos/idiomas.ini";
	}//fun rutaOpciones


	//Devuelve el array de textos o false si el fichero no es válido. RAW: el modo normal altera "No", "Yes" o "${x}".
	private static function leerIni($s_fichero)
	{
		if(array_key_exists($s_fichero, self::$a_ini))
		{
			return self::$a_ini[$s_fichero];
		}//if

		$s_contenido = @file_get_contents($s_fichero);
		$a_datos = ($s_contenido === false) ? false : @parse_ini_string($s_contenido, false, INI_SCANNER_RAW);

		if(is_array($a_datos))
		{
			foreach($a_datos as $s_clave=>$valor)
			{
				//RAW no da error con una comilla sin cerrar ni con una línea sin clave.
				if(!is_string($valor) || strpos($valor, '"') !== false || !preg_match('/^[A-Za-z0-9_]+$/D', (string)$s_clave))
				{
					$a_datos = false;
					break;
				}//if
			}//foreach
		}//if
		else
		{
			$a_datos = false;
		}//else

		if($a_datos === false)
		{
			error_log("CFW: fichero de idioma no válido: ".$s_fichero);
		}//if

		self::$a_ini[$s_fichero] = $a_datos;
		return $a_datos;
	}//fun leerIni


	//Código => nombre de los idiomas con textos.ini válido.
	static function disponibles()
	{
		if(self::$a_disponibles !== null)
		{
			return self::$a_disponibles;
		}//if

		$a_codigos = array();
		foreach(array("general","specific") as $s_zona)
		{
			$a_carpetas = glob(self::rutaBase().$s_zona."/lang/*", GLOB_ONLYDIR);
			foreach(($a_carpetas ?: array()) as $s_carpeta)
			{
				$s_codigo = basename($s_carpeta);
				if(preg_match('/^[a-z]{2}$/D', $s_codigo))
				{
					$a_codigos[$s_codigo] = true;
				}//if
			}//foreach
		}//foreach

		$a_disponibles = array();
		foreach(array_keys($a_codigos) as $s_codigo)
		{
			$s_nombre = self::nombreSiValido($s_codigo);
			if($s_nombre !== false)
			{
				$a_disponibles[$s_codigo] = $s_nombre;
			}//if
		}//foreach
		ksort($a_disponibles);

		self::$a_disponibles = $a_disponibles;
		return $a_disponibles;
	}//fun disponibles


	private static function nombreSiValido($s_codigo)
	{
		$b_existe = false;
		$s_nombre = $s_codigo;
		foreach(array("general","specific") as $s_zona)
		{
			$s_fichero = self::rutaBase().$s_zona."/lang/".$s_codigo."/textos.ini";
			if(is_file($s_fichero))
			{
				$b_existe = true;
				$a_textos = self::leerIni($s_fichero);
				if($a_textos === false)
				{
					return false;
				}//if
				if(isset($a_textos["nombre_idioma"]) && $a_textos["nombre_idioma"] !== "")
				{
					$s_nombre = $a_textos["nombre_idioma"];
				}//if
			}//if
		}//foreach

		return $b_existe ? $s_nombre : false;
	}//fun nombreSiValido


	static function porDefecto()
	{
		if(self::$s_default === null)
		{
			$app = new AppConf();
			$s_default = @$app->config["lang_default"];
			$a_disponibles = self::disponibles();
			self::$s_default = (is_string($s_default) && isset($a_disponibles[$s_default])) ? $s_default : "es";
		}//if

		return self::$s_default;
	}//fun porDefecto


	private static function opciones()
	{
		if(self::$a_opciones !== null)
		{
			return self::$a_opciones;
		}//if

		self::$a_opciones = array("multiidioma" => false, "idiomas" => array());
		$s_fichero = self::rutaOpciones();

		if(!is_file($s_fichero))
		{
			return self::$a_opciones;
		}//if

		$s_contenido = @file_get_contents($s_fichero);
		$a_datos = ($s_contenido === false) ? false : @parse_ini_string($s_contenido, false, INI_SCANNER_RAW);

		if(!is_array($a_datos)
			|| !isset($a_datos["multiidioma"]) || !isset($a_datos["idiomas"])
			|| !in_array($a_datos["multiidioma"], array("0","1"), true)
			|| !is_string($a_datos["idiomas"]) || !preg_match('/^[a-z]{2}(,[a-z]{2})*$/D', $a_datos["idiomas"]))
		{
			error_log("CFW: fichero de opciones de idioma ilegible o no válido: ".$s_fichero);
			return self::$a_opciones;
		}//if

		self::$a_opciones = array("multiidioma" => $a_datos["multiidioma"] === "1", "idiomas" => explode(",", $a_datos["idiomas"]));
		return self::$a_opciones;
	}//fun opciones


	static function multiidiomaActivo()
	{
		$a_opciones = self::opciones();
		return $a_opciones["multiidioma"];
	}//fun multiidiomaActivo


	//Los marcados en la configuración, aunque el multiidioma esté desactivado. El de por defecto siempre va.
	static function seleccionados()
	{
		$a_opciones = self::opciones();
		$a_disponibles = self::disponibles();
		$a_seleccionados = array(self::porDefecto());

		foreach($a_opciones["idiomas"] as $s_codigo)
		{
			if(isset($a_disponibles[$s_codigo]) && !in_array($s_codigo, $a_seleccionados, true))
			{
				$a_seleccionados[] = $s_codigo;
			}//if
		}//foreach

		return $a_seleccionados;
	}//fun seleccionados


	static function activos()
	{
		return self::multiidiomaActivo() ? self::seleccionados() : array(self::porDefecto());
	}//fun activos


	static function hayVarios()
	{
		return count(self::activos()) >= 2;
	}//fun hayVarios


	//Se calcula en cada llamada: el login y el logout cambian el idioma en mitad de la petición.
	static function actual()
	{
		$s_default = self::porDefecto();

		if(!isset($_SESSION) || !self::multiidiomaActivo())
		{
			return $s_default;
		}//if

		if(isset($_SESSION["logueado"]) && $_SESSION["logueado"] != "")
		{
			$s_lang = isset($_SESSION["lang"]) ? $_SESSION["lang"] : "";
			return (is_string($s_lang) && in_array($s_lang, self::activos(), true)) ? $s_lang : $s_default;
		}//if

		return self::detectarNavegador();
	}//fun actual


	//Único punto de lectura de Accept-Language. Devuelve siempre un elemento de la lista de activos.
	static function detectarNavegador()
	{
		$a_activos = self::activos();
		$s_default = self::porDefecto();

		if(count($a_activos) < 2)
		{
			return $s_default;
		}//if

		if(!self::$b_vary_enviado && !headers_sent())
		{
			header("Vary: Accept-Language", false);
			self::$b_vary_enviado = true;
		}//if

		$s_cache = implode(",", $a_activos);
		if(!isset(self::$a_navegador[$s_cache]))
		{
			$s_cabecera = isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? $_SERVER["HTTP_ACCEPT_LANGUAGE"] : "";
			self::$a_navegador[$s_cache] = self::elegirIdioma($s_cabecera, $a_activos, $s_default);
		}//if

		return self::$a_navegador[$s_cache];
	}//fun detectarNavegador


	private static function elegirIdioma($s_cabecera, $a_activos, $s_default)
	{
		if(!is_string($s_cabecera) || $s_cabecera === "" || strlen($s_cabecera) > 512)
		{
			return $s_default;
		}//if

		$a_elementos = explode(",", $s_cabecera);
		if(count($a_elementos) > 32)
		{
			return $s_default;
		}//if

		$a_pesos = array();
		foreach($a_elementos as $s_elemento)
		{
			if(!preg_match('/^[ \t]*([A-Za-z]{1,8})(?:-[A-Za-z0-9]{1,8})*[ \t]*(?:;[ \t]*[qQ]=(0(?:\.[0-9]{0,3})?|1(?:\.0{0,3})?)[ \t]*)?$/D', $s_elemento, $a_match))
			{
				continue;
			}//if

			$s_primaria = strtolower($a_match[1]);
			$f_peso = isset($a_match[2]) ? (float)$a_match[2] : 1.0;
			if(!isset($a_pesos[$s_primaria]) || $f_peso > $a_pesos[$s_primaria])
			{
				$a_pesos[$s_primaria] = $f_peso;
			}//if
		}//foreach

		$s_elegido = $s_default;
		$f_mejor = 0.0;
		foreach($a_pesos as $s_primaria=>$f_peso)
		{
			$i_pos = array_search((string)$s_primaria, $a_activos, true);
			//Mayor estricto: en un empate gana la primera de la cabecera.
			if($i_pos !== false && $f_peso > $f_mejor)
			{
				$s_elegido = $a_activos[$i_pos];
				$f_mejor = $f_peso;
			}//if
		}//foreach

		return $s_elegido;
	}//fun elegirIdioma


	//Fusión por clave: general y specific del idioma por defecto, y encima los del idioma actual.
	static function textos($s_nombre)
	{
		if(!is_string($s_nombre) || !preg_match('#^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*$#D', $s_nombre))
		{
			return array();
		}//if

		$s_lang = self::actual();
		$s_cache = $s_lang."|".$s_nombre;
		if(isset(self::$a_textos[$s_cache]))
		{
			return self::$a_textos[$s_cache];
		}//if

		$a_idiomas = array(self::porDefecto());
		if($s_lang !== $a_idiomas[0])
		{
			$a_idiomas[] = $s_lang;
		}//if

		$a_textos = array();
		foreach($a_idiomas as $s_codigo)
		{
			foreach(array("general","specific") as $s_zona)
			{
				$s_fichero = self::rutaBase().$s_zona."/lang/".$s_codigo."/".$s_nombre.".ini";
				if(is_file($s_fichero))
				{
					$a_fichero = self::leerIni($s_fichero);
					if($a_fichero !== false)
					{
						$a_textos = array_merge($a_textos, $a_fichero);
					}//if
				}//if
			}//foreach
		}//foreach

		self::$a_textos[$s_cache] = $a_textos;
		return $a_textos;
	}//fun textos


	//Falso si falta el fichero de opciones o no se puede escribir en su carpeta.
	static function opcionesPreparadas()
	{
		$s_fichero = self::rutaOpciones();

		clearstatcache();
		return is_file($s_fichero) && is_readable($s_fichero) && is_writable(dirname($s_fichero));
	}//fun opcionesPreparadas


	//Reemplazo atómico con temporal y rename: Apache solo necesita permiso sobre la carpeta datos.
	static function guardar($b_activo, $a_codigos)
	{
		$a_disponibles = self::disponibles();
		$a_idiomas = array(self::porDefecto());
		foreach((array)$a_codigos as $s_codigo)
		{
			if(is_string($s_codigo) && isset($a_disponibles[$s_codigo]) && !in_array($s_codigo, $a_idiomas, true))
			{
				$a_idiomas[] = $s_codigo;
			}//if
		}//foreach

		$s_contenido = 'multiidioma = "'.($b_activo ? "1" : "0").'"'."\n".'idiomas = "'.implode(",", $a_idiomas).'"'."\n";
		$s_fichero = self::rutaOpciones();

		if(!self::escribirAtomico($s_fichero, $s_contenido))
		{
			error_log("CFW: no se pudo escribir ".$s_fichero);
			return false;
		}//if

		self::$a_opciones = null;
		self::$a_textos = array();
		self::$a_navegador = array();

		return true;
	}//fun guardar


	private static function escribirAtomico($s_fichero, $s_contenido)
	{
		$s_carpeta = dirname($s_fichero);

		clearstatcache();
		if(!is_dir($s_carpeta) || !is_writable($s_carpeta))
		{
			return false;
		}//if

		$s_tmp = @tempnam($s_carpeta, "idiomas_");
		if($s_tmp === false)
		{
			return false;
		}//if

		//tempnam cae al temporal del sistema si no puede usar la carpeta.
		$b_ok = dirname($s_tmp) === realpath($s_carpeta);
		if($b_ok)
		{
			$fd = @fopen($s_tmp, "w");
			$b_ok = $fd && fwrite($fd, $s_contenido) === strlen($s_contenido) && fflush($fd) && fsync($fd);
			if($fd)
			{
				fclose($fd);
			}//if

			//tempnam crea con 0600 y rename lo conserva.
			$b_ok = $b_ok && @chmod($s_tmp, 0640) && @rename($s_tmp, $s_fichero);
		}//if

		if(!$b_ok)
		{
			@unlink($s_tmp);
		}//if

		return $b_ok;
	}//fun escribirAtomico
}//class
