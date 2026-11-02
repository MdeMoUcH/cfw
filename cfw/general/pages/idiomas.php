<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/


class idiomas extends Page{
	
	function __construct(){
		parent::__construct();
		$this->exigirAdmin();
		$this->vars["menu"] = $this->get_menu("idiomas");
	}
	
	function init(){
		if(@$_SERVER["REQUEST_METHOD"] === "POST"){
			$this->guardarOpciones();
		}
		
		$a_disponibles = GestorIdiomas::disponibles();
		$a_seleccionados = GestorIdiomas::seleccionados();
		$s_default = GestorIdiomas::porDefecto();
		
		$a_opciones = array();
		foreach($a_disponibles as $s_codigo=>$s_nombre){
			$a_opciones[$s_codigo] = array("nombre" => $s_nombre, "value" => $s_codigo, "checked" => in_array($s_codigo,$a_seleccionados,true));
		}
		
		$a_campos = array(
			"multiidioma" => array(
				"title" => t("text_idiomas_activar"),
				"name" => "multiidioma",
				"type" => "checkbox_single",
				"value" => "1",
				"checked" => GestorIdiomas::multiidiomaActivo()),
			"idiomas" => array(
				"title" => t("text_idiomas_lista"),
				"name" => "idiomas",
				"type" => "checkbox",
				"opciones" => $a_opciones)
		);
		
		$form = new Form();
		$form->vars["name"] = "formularioidiomas";
		$form->setAction($this->config["urlbase"]."admin/idiomas");
		$form->setMethod("POST");
		$form->addContentTable($a_campos);
		
		$tplnota = new Template("html/p");
		$s_nombre_default = isset($a_disponibles[$s_default]) ? $a_disponibles[$s_default] : $s_default;
		$tplnota->vars["text"] = t("text_idiomas_default",array("idioma" => escapar($s_nombre_default)));
		
		$tpl = new Template("usuarios");
		$tpl->vars["titulo"] = t("text_idiomas_titulo");
		$tpl->vars["content"] = $this->leerFlash().$tplnota->get().$form->getForm();
		
		$this->show($tpl);
	}
	
	
	protected function guardarOpciones(){
		$activo = @$_POST["multiidioma"];
		if(is_array($activo)){
			$activo = @$activo[0];
		}
		
		$b_ok = GestorIdiomas::guardar($activo === "1",(array)@$_POST["idiomas"]);
		
		if($b_ok){
			$this->guardarFlash("Green","text_idiomas_guardado");
		}else{
			$this->guardarFlash("Red","text_idiomas_error_guardar");
		}
		$this->redirigir($this->config["urlbase"]."admin/idiomas");
	}
	
}//class
