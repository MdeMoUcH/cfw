<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/

class errorpage extends Page{	
	
	function init(){
		http_response_code(404);
		$this->showError(t("text_error_404"));
	}
}//class
