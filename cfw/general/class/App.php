<?
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 
 * 2010
 * 
 ***************/



class App extends AppConf
{
	public $errores;
	public $session;
	
	function __construct()
	{
		parent::__construct();
		
		$this->errores = array();
		$this->session = new Msession($this->config);
	}//__construct
	
	function addError($s_error)
	{
		if($s_error != "")
		{
			$this->errores[] = $s_error;
		}
	}//fun addError
	
	function showError($s_error = "")
	{
		$this->addError($s_error);
		$page = new ErrorCFW();
		$page->init($this->errores);
		die();
	}//fun showError
}//class
