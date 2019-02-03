<?
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 
 * 2010
 * 
 ***************/



class ErrorCFW extends Page
{	
	function init($errores = array()){
		$vars = array();
		if(count($errores) >= 1)
		{
			$s_errores = "";
			foreach($errores as $error)
			{
				$s_errores .= "<h2>".$error."</h2>";
			}
			$vars["error"] = $s_errores;
		}
		
		$tpl = new Template("errores");
		
		$this->vars["menu"] = $this->get_menu("");
		
		$tpl->set_vars($vars);
		
		$this->show($tpl);
	}//init
}//class
