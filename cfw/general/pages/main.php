<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/


class main extends Page {
	
	protected $a_plantillas_permitidas = array("portada");
	
	function __construct(){
		parent::__construct();
	}
	
	function init($arg1 = "portada", $arg2 = ""){
		if(!in_array((string)$arg1,$this->a_plantillas_permitidas,true)){
			http_response_code(404);
			$this->showError(t("text_pagina_no_existe"));
		}
		
		$this->vars["menu"] = $this->get_menu($arg1 === "portada" ? "main" : $arg1);
		$tpl = new Template($arg1);
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		
		$this->show($tpl);
	}
}//class
