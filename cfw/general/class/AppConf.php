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
			self::$a_config = parse_ini_file(dirname(__DIR__, 2)."/config.ini");
		}//if
		$this->config = self::$a_config;
		
		if($this->config["desarrollo"])
		{
			error_reporting(E_ALL);
		}//if
		else
		{
			error_reporting(0);
		}//else
	}//__construct
}//class
