<?php
/***************
 *
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 *
 ***************/


//Clave RSA con la que el navegador cifra las contraseñas (RSA-OAEP con SHA-1, js/cifrado.js).
class Cifrado
{
	private static $clave = null;
	private static $a_detalles = array();


	//cifrado.clave_privada de config.ini, si está; si no, la de cfw/data.
	//false si está pero no es absoluta (vacía, off/no/false/none, array o relativa).
	static function ruta()
	{
		$app = new AppConf();
		if(!is_array($app->config) || !array_key_exists("cifrado.clave_privada", $app->config))
		{
			return dirname(__DIR__,2)."/data/clave_privada.pem";
		}//if

		$s_ruta = is_string($app->config["cifrado.clave_privada"]) ? trim($app->config["cifrado.clave_privada"]) : "";
		$s_absoluta = PHP_OS_FAMILY === "Windows" ? '#^[A-Za-z]:[\\\\/]#' : '#^/#';

		return preg_match($s_absoluta, $s_ruta) === 1 ? $s_ruta : false;
	}//fun ruta


	//La genera en el primer uso; si existe y no es válida, no la sobrescribe.
	static function clavePrivada()
	{
		if(self::$clave !== null)
		{
			return self::$clave;
		}//if

		self::$clave = false;

		if(!function_exists("openssl_pkey_new"))
		{
			error_log("CFW: falta la extensión openssl");
			return false;
		}//if

		$s_ruta = self::ruta();
		if($s_ruta === false)
		{
			error_log("CFW: cifrado.clave_privada no es una ruta absoluta (vacía, off/no/false/none o relativa)");
			return false;
		}//if

		clearstatcache();
		if(!self::carpetaSegura(dirname($s_ruta)))
		{
			return false;
		}//if

		//Si ya hay algo en la ruta (también un enlace roto), no se genera: se valida.
		if(@lstat($s_ruta) === false && !self::generar($s_ruta))
		{
			self::vaciarErrores();
			return false;
		}//if

		$s_pem = self::leer($s_ruta);
		if($s_pem === false)
		{
			return false;
		}//if

		$clave = @openssl_pkey_get_private($s_pem);
		$a_detalles = $clave ? @openssl_pkey_get_details($clave) : false;
		self::vaciarErrores();

		if(!$a_detalles || $a_detalles["type"] !== OPENSSL_KEYTYPE_RSA || $a_detalles["bits"] < 2048)
		{
			error_log("CFW: clave RSA no válida: ".$s_ruta);
			return false;
		}//if

		self::$a_detalles = $a_detalles;
		self::$clave = $clave;

		return $clave;
	}//fun clavePrivada


	static function clavePublica()
	{
		return self::clavePrivada() ? self::$a_detalles["key"] : false;
	}//fun clavePublica


	//Un dato no válido es un caso normal (formulario antiguo o manipulado): no va al log.
	static function descifrar($s_b64)
	{
		if(!is_string($s_b64) || !($clave = self::clavePrivada()))
		{
			return false;
		}//if

		$s_bin = base64_decode($s_b64, true);
		if($s_bin === false || strlen($s_bin) !== (int)ceil(self::$a_detalles["bits"] / 8))
		{
			return false;
		}//if

		//Sin el quinto parámetro (8.5): así funciona igual desde 8.2, con OAEP y SHA-1.
		$s_claro = "";
		$b_ok = @openssl_private_decrypt($s_bin, $s_claro, $clave, OPENSSL_PKCS1_OAEP_PADDING);
		self::vaciarErrores();

		return $b_ok ? $s_claro : false;
	}//fun descifrar


	private static function generar($s_ruta)
	{
		$s_carpeta = dirname($s_ruta);
		$s_tmp = false;
		$s_pem = "";

		$b_ok = is_dir($s_carpeta) && is_writable($s_carpeta);
		if($b_ok)
		{
			$clave = @openssl_pkey_new(array("private_key_bits" => 3072, "private_key_type" => OPENSSL_KEYTYPE_RSA));
			$b_ok = $clave !== false && @openssl_pkey_export($clave, $s_pem);
		}//if

		if($b_ok)
		{
			$s_tmp = @tempnam($s_carpeta, "clave_");
			//tempnam cae al temporal del sistema si no puede usar la carpeta.
			$b_ok = $s_tmp !== false && dirname($s_tmp) === realpath($s_carpeta);
		}//if

		if($b_ok)
		{
			$fd = @fopen($s_tmp, "w");
			$b_ok = $fd && fwrite($fd, $s_pem) === strlen($s_pem) && fflush($fd) && fsync($fd);
			if($fd)
			{
				fclose($fd);
			}//if
			$b_ok = $b_ok && @chmod($s_tmp, 0600);
		}//if

		//link no pisa la clave que otra petición haya creado a la vez; rename, para sistemas sin enlaces duros.
		if($b_ok && !@link($s_tmp, $s_ruta))
		{
			clearstatcache();
			$b_ok = is_file($s_ruta) || @rename($s_tmp, $s_ruta);
		}//if

		if($s_tmp !== false)
		{
			@unlink($s_tmp);
		}//if

		if(!$b_ok)
		{
			error_log("CFW: no se pudo generar ".$s_ruta.": ".openssl_error_string());
		}//if
		elseif(PHP_OS_FAMILY !== "Windows" && !function_exists("posix_geteuid"))
		{
			//Solo al generarla, para no escribir una línea por petición.
			error_log("CFW: sin la extensión posix no se comprueba el propietario de la clave privada");
		}//elseif

		return $b_ok;
	}//fun generar


	//Se admite la escritura de grupo (cfw/data en 775 o 770 con el grupo de Apache), no la de todos.
	private static function carpetaSegura($s_carpeta)
	{
		$a_stat = @stat($s_carpeta);
		if(PHP_OS_FAMILY === "Windows" || !$a_stat || !($a_stat["mode"] & 0002))
		{
			return true;
		}//if

		error_log("CFW: la carpeta de la clave privada la pueden escribir todos los usuarios: ".$s_carpeta);
		return false;
	}//fun carpetaSegura


	//Archivo normal (sin seguir enlaces), del usuario del proceso (solo con posix) y sin escritura de grupo ni de otros.
	private static function leer($s_ruta)
	{
		$s_pem = false;
		$s_error = "";
		$a_lstat = @lstat($s_ruta);
		$fd = false;
		$b_unix = PHP_OS_FAMILY !== "Windows";

		if(!$a_lstat || ($a_lstat["mode"] & 0170000) !== 0100000)
		{
			$s_error = "es un enlace o no es un archivo normal";
		}//if
		elseif(!($fd = @fopen($s_ruta, "r")) || !($a_stat = fstat($fd))
			|| ($b_unix && ($a_stat["dev"] !== $a_lstat["dev"] || $a_stat["ino"] !== $a_lstat["ino"])))
		{
			$s_error = "no se pudo abrir";
		}//elseif
		elseif($b_unix && function_exists("posix_geteuid") && $a_stat["uid"] !== ($i_uid = posix_geteuid()))
		{
			$s_error = "no es del usuario del proceso (uid ".$i_uid.")";
		}//elseif
		elseif($b_unix && ($a_stat["mode"] & 0022))
		{
			$s_error = "la pueden escribir el grupo u otros usuarios";
		}//elseif
		else
		{
			$s_pem = stream_get_contents($fd);
		}//else

		if($fd)
		{
			fclose($fd);
		}//if

		if($s_pem === false)
		{
			error_log("CFW: clave privada rechazada, ".($s_error !== "" ? $s_error : "no se pudo leer").": ".$s_ruta);
		}//if

		return $s_pem;
	}//fun leer


	private static function vaciarErrores()
	{
		while(function_exists("openssl_error_string") && openssl_error_string() !== false)
		{
		}//while
	}//fun vaciarErrores
}//class
