<?
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/


class main extends Page {
	
	function __construct(){
		parent::__construct();
	}
	
	function init($arg1 = "main", $arg2 = ""){
		$this->vars["menu"] = $this->get_menu($arg1);
		$tpl = new Template($arg1);
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		
		$this->show($tpl);
	}
}//class
