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
		return self::rutaBase()."data/idiomas.ini";
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


	//Como Cifrado::leer: solo un archivo normal, sin seguir enlaces ni abrir un FIFO. Null si no hay nada o se rechaza, false si no se puede leer.
	private static function leerOpciones($s_fichero)
	{
		$b_unix = PHP_OS_FAMILY !== "Windows";

		//Segundo intento por si un guardado hace rename entre el lstat y el fopen.
		for($i = 0; $i < 2; $i++)
		{
			clearstatcache(true, $s_fichero);
			$a_lstat = @lstat($s_fichero);
			if(!$a_lstat)
			{
				return null;
			}//if

			if(($a_lstat["mode"] & 0170000) !== 0100000)
			{
				break;
			}//if

			$fd = @fopen($s_fichero, "r");
			if(!$fd)
			{
				return false;
			}//if

			$a_stat = fstat($fd);
			if($a_stat && ($a_stat["mode"] & 0170000) === 0100000
				&& (!$b_unix || ($a_stat["dev"] === $a_lstat["dev"] && $a_stat["ino"] === $a_lstat["ino"])))
			{
				$s_contenido = stream_get_contents($fd);
				fclose($fd);
				return $s_contenido;
			}//if

			fclose($fd);
		}//for

		error_log("CFW: fichero de opciones de idioma rechazado, es un enlace o no es un archivo normal: ".$s_fichero);
		return null;
	}//fun leerOpciones


	private static function opciones()
	{
		if(self::$a_opciones !== null)
		{
			return self::$a_opciones;
		}//if

		self::$a_opciones = array("multiidioma" => false, "idiomas" => array());
		$s_fichero = self::rutaOpciones();

		$s_contenido = self::leerOpciones($s_fichero);
		if($s_contenido === null)
		{
			return self::$a_opciones;
		}//if

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


	//Falso si falta el fichero de opciones, no es un archivo normal (sin seguir enlaces) o no se puede escribir en su carpeta.
	static function opcionesPreparadas()
	{
		$s_fichero = self::rutaOpciones();

		clearstatcache();
		$a_lstat = @lstat($s_fichero);
		return $a_lstat && ($a_lstat["mode"] & 0170000) === 0100000 && is_readable($s_fichero) && is_writable(dirname($s_fichero));
	}//fun opcionesPreparadas


	//Reemplazo atómico con temporal y rename: Apache solo necesita permiso sobre la carpeta data.
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


	//Mismo patrón que Cifrado::generar, pero con rename: gana el último guardado.
	private static function escribirAtomico($s_fichero, $s_contenido)
	{
		$s_carpeta = dirname($s_fichero);
		$s_tmp = false;
		$b_unix = PHP_OS_FAMILY !== "Windows";

		clearstatcache();
		if(!is_dir($s_carpeta) || !is_writable($s_carpeta))
		{
			return false;
		}//if

		try
		{
			$s_nombre = $s_carpeta."/idiomas_".bin2hex(random_bytes(16)).".tmp";
		}//try
		catch(\Exception $e)
		{
			$s_nombre = "";
		}//catch

		//"x" no abre un archivo que ya exista; con esta umask se crea en 0640.
		$i_umask = umask(0027);
		$fd = $s_nombre !== "" ? @fopen($s_nombre, "x") : false;
		umask($i_umask);
		$b_ok = $fd !== false;

		if($b_ok)
		{
			//Solo se borra lo que ha creado este fopen.
			$s_tmp = $s_nombre;

			//"x" sigue los enlaces rotos: antes de escribir, lo abierto debe ser el archivo de la ruta.
			$a_stat = fstat($fd);
			clearstatcache(true, $s_tmp);
			$a_lstat = @lstat($s_tmp);
			$b_ok = $a_stat && $a_lstat && ($a_lstat["mode"] & 0170000) === 0100000
				&& (!$b_unix || ($a_stat["dev"] === $a_lstat["dev"] && $a_stat["ino"] === $a_lstat["ino"]));

			//Sin chmod, que sigue enlaces (PHP no tiene fchmod): se acepta el modo creado, 0640 u otro de una ACL por defecto, si no da nada a otros ni ejecución.
			$i_modo = $a_stat ? $a_stat["mode"] & 0777 : 0;
			$b_ok = $b_ok && (!$b_unix || (($i_modo & ~0660) === 0 && ($i_modo & 0600) === 0600));

			$b_ok = $b_ok && fwrite($fd, $s_contenido) === strlen($s_contenido) && fflush($fd) && fsync($fd);
			fclose($fd);
		}//if

		//rename sustituye un destino que sea un enlace sin seguirlo.
		$b_ok = $b_ok && @rename($s_tmp, $s_fichero);

		if(!$b_ok && $s_tmp !== false)
		{
			@unlink($s_tmp);
		}//if

		return $b_ok;
	}//fun escribirAtomico
}//class
