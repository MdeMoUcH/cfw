<?
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/




class Form //extends App
{
	
	function __construct(){
		//parent::__construct();
		
		$this->vars  = array();
		$this->vars["name"] = "";
		$this->vars["action"] = "";
		$this->vars["method"] = "";
		$this->vars["enctype"] = "";
		$this->vars["content"] = "";
		
		$this->a_default_buttons = array();
		//$this->a_default_buttons[] = array("name" => "button","value" => "Enviar","onclick" => "");
		$this->a_default_buttons[] = array("name" => "button","class"=>"button","value" => "Guardar","onclick" => "");
	}
	
	function setAction($action){
		$tpl = new Template("html/action");
		$tpl->vars["action"] = $action;
		$this->vars["action"] = $tpl->get();
	}
	
	function setName($name){
		$this->vars["name"] = $name;
	}
	
	function setMethod($method){
		$tpl = new Template("html/method");
		$tpl->vars["method"] = $method;
		$this->vars["method"] = $tpl->get();
	}
	
	function activeEnctype(){
		$tpl = new Template("html/enctype");
		$this->vars["enctype"] = $tpl->get();
	}
	
	function getForm(){		
		$tpl = new Template("html/form");
		
		return $tpl->get($this->vars);
	}
	
	function get(){
		return $this->getForm();
	}
	
	function getInput($name = "", $type = "", $value = "", $onclick = "", $class = "", $checked = false, $text = ""){
		$tpl = new Template("html/input");
		
		$tpl->vars["name"] = $name;
		$tpl->vars["type"] = $type;
		$tpl->vars["text"] = $text;
		if($type != "password"){
			$tpl->vars["value"] = $value;
		}
		$tpl->vars["onclick"] = "";
		if($onclick != ""){
			$tplclick = new Template("html/onclick");
			$tplclick->vars["onclick"] = $onclick;
			$tpl->vars["onclick"] = $tplclick->get();
		}	
		$tpl->vars["class"] = "";
		if($class != ""){
			$tplclass = new Template("html/class");
			$tplclass->vars["class"] = $class;
			$tpl->vars["class"] = $tplclass->get();
		}	
		if($checked){
			$tplcheck = new Template("html/checked");
			$tpl->vars["checked"] = $tplcheck->get();
		}
		
		return $tpl->get();
	}
	
	
	function getTextarea($name = "", $value = "", $onclick = "", $rows = "", $cols = "", $class = "", $text = ""){
		$tpl = new Template("html/textarea");
		$tpl->vars["name"] = $name;
		$tpl->vars["value"] = $value;
		$tpl->vars["text"] = $text;
		if($rows != ""){
			$tpl->vars["rows"] = $rows;
		}else{
			$tpl->vars["rows"] = 8;
		}
		if($cols != ""){
			$tpl->vars["cols"] = $cols;
		}else{
			$tpl->vars["cols"] = 32;
		}
		$tpl->vars["onclick"] = "";
		if($onclick != ""){
			$tplclick = new Template("html/onclick");
			$tplclick->vars["onclick"] = $tplclick->get();
			$tpl->vars["onclick"] = $onclick;
		}
		$tpl->vars["class"] = "";
		if($class != ""){
			$tplclass = new Template("html/class");
			$tplclass->vars["class"] = $class;
			$tpl->vars["class"] = $tplclass->get();
		}
		
		return $tpl->get();
	}
	
	
	function getRow($title = "", $name = "", $type = "", $value = "", $onclick = "", $row_type = "", $class = "", $opciones = array(), $checked = false, $tabla = ""){
		switch($type){
			case "cabecera":
					$tpl = new Template("html/form_cabecera");
					$tpl->vars["title"] = $title;
					return $tpl->get();
				break;
			case "hidden":
					$row_type = "_colspan";
				break;
			case "num":
					$type = "text";
				break;
			case "img":
					$type = "file";
			case "file":	
					$this->activeEnctype();
				break;
			case "textarea":
					$tpl = new Template("html/form_row_textarea");
					$tpl->vars["title"] = $title;
					$tpl->vars["textarea"] = $this->getTextarea($name,$value,$onclick,"","",$class);			
					return $tpl->get();
				break;
			case "checkbox_single":
					$tpl = new Template("html/form_row");
					//if($value == 1){$checked = true;}
					$tpl->vars["input"] = $this->getInput($name,"checkbox",$value,$onclick,$class,$checked,"&nbsp;".$title);
					return $tpl->get();
				break;
			case "checkbox_dinamico":
					$orm_aux = new DinamicOrm();
					$orm_aux->setTabla($tabla);
					$orm_aux->getAll();
					$a_opciones = array();
					$a_checked = array();
					foreach($orm_aux->resultado as $opcion){
						if(@$opcion["nombre"] != ""){
							$a_opciones[$opcion["nombre"]] = $opcion["id"];
						}elseif(@$opcion["nick"] != ""){
							$a_opciones[$opcion["nick"]] = $opcion["id"];
						}else{
							$a_opciones[$opcion["name"]] = $opcion["id"];
						}
						
						if($opcion["id"] == @$opciones[$opcion["id"]]){
							$a_checked[$opcion["id"]] = true;
						}else{
							$a_checked[$opcion["id"]] = false;
						}	
						
					}
					$opciones = $a_opciones;
					
					$type = "checkbox";
			case "checkbox":
			case "radio":
					$tpl = new Template("html/form_row");
					$tpl->vars["title"] = $title;
					$tpl->vars["input"] = "";
					
					foreach($opciones as $option=>$valueo){
						if(is_array($valueo)){
							if($valueo["checked"]){
								$checked = true;
							}else{
								$checked = false;
							}
							$option = $valueo["nombre"];
							$valueo = $valueo["value"];
						}else{
							if($value == $valueo){
								$checked = true;
							}else{
								$checked = false;
							}
							if(@$a_checked[$valueo]){
								$checked = true;
							}
						}
						
						
						/* !!! Metido para el caso de que sólo haya un elemento, hay que comprobar que no dé problemas....*/
						if(count($opciones) <= 1) $name_aux = $name;
						else $name_aux = $name."[]";
						/* !!! */
						
						
						$tpl->vars["input"] .= $this->getInput($name_aux,$type,$valueo,$onclick,$class,$checked,"&nbsp;".$option)."<br/>";
					}
					
					return $tpl->get();
				break;
			case "select":
			case "select_dinamico":
			case "select_dinamico_desactivado":
			
					$tpl = new Template("html/select");
					$tpl->vars["id"] = $name;
					$tpl->vars["name"] = $name;
					$tpl->vars["options"] = "";
					if($type == "select_dinamico_desactivado" || $type == "select_desactivado"){
						$tpl_aux_1 = new Template("html/disabled");
						$tpl->vars["disabled"] = $tpl_aux_1->get();
					}
					
					if(($type == "select_dinamico" || $type == "select_dinamico_desactivado") && count($opciones) <= 0){
						$orm_aux = new DinamicOrm();
						$orm_aux->setTabla($tabla);
						$orm_aux->getAll();
						$a_opciones = array();
						foreach($orm_aux->resultado as $opcion){
							if(@$opcion["name"] != ""){
								$a_opciones[$opcion["id"]] = utf8_encode($opcion["name"]);
							}else{
								$a_opciones[$opcion["id"]] = utf8_encode($opcion["nombre"]);
							}
						}
						$opciones = $a_opciones;
					}
					
					foreach($opciones as $valueo=>$text){
						$tpl_aux = new Template("html/option");
						$tpl_aux->vars["name"] = $text;
						$tpl_aux->vars["value"] = $valueo;
						if($value == $valueo){
							$tpl_aux_2 = new Template("html/selected");
							$tpl_aux->vars["selected"] = $tpl_aux_2->get();
						}
						
						$tpl->vars["options"] .= $tpl_aux->get();
					}
					$tpl_aux = new Template("html/option");
						$tpl_aux->vars["name"] = "-";
						$tpl_aux->vars["value"] = "";
					$tpl->vars["options"] = $tpl_aux->get().$tpl->vars["options"];
					
					$tpl_last = new Template("html/form_row");
					$tpl_last->vars["title"] = $title;
					$tpl_last->vars["input"] = $tpl->get();
					
					return $tpl_last->get();
				break;
			case "fecha":
					$class = "datepicker";
				break;
		}
		
		$tpl = new Template("html/form_row".$row_type);
		
		if($type != ''){
			$tpl->vars["title"] = $title;
			$tpl->vars["input"] = $this->getInput($name, $type, $value, $onclick, $class);
		}else{
			$tpl->vars["title"] = '<p style="margin-top:25px;text-decoration: underline;">'.$title.'</p>';
			$tpl->vars["input"] = '<p style="height:50px;"></p>';
		}
		return $tpl->get();
	}
	
	
	function addContentTable($a_rows, $a_buttons = array(), $a_values = array()){
		
		$s_content = "";
		foreach($a_rows as $row){
			if(@$a_values[$row["name"]] != ""){
				if(is_array($a_values[$row["name"]])){
					$row["value"] = $a_values[$row["name"]][0];
				}else{
					$row["value"] = $a_values[$row["name"]];
				}
			}
			if(@$row["type"] == "checkbox_single" && @$a_values[$row["name"]] == 1){
				$row["checked"] = true;
			}
			$s_content .= $this->getRow($row["title"],@$row["name"],@$row["type"],@$row["value"],@$row["onclick"],"",@$row["class"],@$row["opciones"],@$row["checked"],@$row["tabla"]);
		}
		
		if(count($a_buttons) < 1 || $a_buttons == ""){
			$a_buttons = $this->a_default_buttons;
		}
		
		foreach($a_buttons as $row){
			$type = "button";
			if((!isset($row["type"]) || $row["type"] == "") && (!isset($row["onclick"]) || $row["onclick"] == "")){
				$type = "submit";
			}
			$s_content .= $this->getRow("",$row["name"],$type,@$row["value"],@$row["onclick"],"_colspan",@$row["class"]);
		}
		
		$tpl = new Template("html/form_main");
		$tpl->vars["content"] = $s_content;
		
		$this->vars["content"] = $tpl->get();
	}
	
}//class
