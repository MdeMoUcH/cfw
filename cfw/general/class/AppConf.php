<?
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
	
	function __construct()
	{	
		$this->errores = Array();
		
		$this->config = parse_ini_file("cfw/config.ini");
		
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
