<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/




class FormCFW //extends App
{
	public $vars;
	public $a_default_buttons;
	//Ids ya usados en la petición; se inicializa con los ids literales de las plantillas.
	private static $a_ids = null;
	private $s_id_csrf = null;
	//Lo generado por setMethod y addContentTable, para regenerarlo con ids nuevos si se pinta otra vez.
	private $s_csrf_valor = null;
	private $s_csrf_input = null;
	private $b_csrf_pintado = false;
	private $a_args_content = null;
	private $s_content = null;
	private $b_content_pintado = false;
	
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
		$this->a_default_buttons[] = array("name" => "button","class"=>"button","value" => t("text_guardar"),"onclick" => "");
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
		
		//En GET no se añade: el token acabaría en la URL.
		$this->vars["csrf_input"] = "";
		$this->s_csrf_input = null;
		if(strtoupper($method) == "POST" && isset($_SESSION["csrf"])){
			if($this->s_id_csrf === null || $this->b_csrf_pintado){
				$this->s_id_csrf = self::idUnico("csrf");
			}
			$this->s_csrf_valor = $_SESSION["csrf"];
			$this->s_csrf_input = $this->input("csrf","hidden",$this->s_csrf_valor,"","",false,"",$this->s_id_csrf);
			$this->b_csrf_pintado = false;
			$this->vars["csrf_input"] = $this->s_csrf_input;
		}
	}
	
	function activeEnctype(){
		$tpl = new Template("html/enctype");
		$this->vars["enctype"] = $tpl->get();
	}
	
	function getForm(){		
		$tpl = new Template("html/form");
		
		//Pintado otra vez: lo generado aquí se regenera con ids nuevos (repite las consultas de los campos dinámicos).
		if($this->s_csrf_input !== null && $this->b_csrf_pintado && isset($this->vars["csrf_input"]) && $this->vars["csrf_input"] === $this->s_csrf_input){
			$this->s_id_csrf = self::idUnico("csrf");
			$this->s_csrf_input = $this->input("csrf","hidden",$this->s_csrf_valor,"","",false,"",$this->s_id_csrf);
			$this->vars["csrf_input"] = $this->s_csrf_input;
		}
		if($this->s_content !== null && $this->b_content_pintado && isset($this->vars["content"]) && $this->vars["content"] === $this->s_content){
			$this->addContentTable($this->a_args_content[0], $this->a_args_content[1], $this->a_args_content[2]);
		}
		$this->b_csrf_pintado = true;
		$this->b_content_pintado = true;
		
		$s_nombre = isset($this->vars["name"]) && is_scalar($this->vars["name"]) ? (string)$this->vars["name"] : "";
		$a_vars = $this->vars;
		$a_vars["name"] = escapar($s_nombre);
		$a_vars["id"] = escapar(self::idUnico($s_nombre !== "" ? $s_nombre : "form"));
		return $tpl->get($a_vars);
	}
	
	function get(){
		return $this->getForm();
	}
	
	function getInput($name = "", $type = "", $value = "", $onclick = "", $class = "", $checked = false, $text = "", $id = ""){
		return $this->input($name, $type, $value, $onclick, $class, $checked, $text, self::idUnico($id === "" ? $name : $id));
	}
	
	//Como getInput, con el id ya reservado.
	private function input($name, $type, $value, $onclick, $class, $checked, $text, $id){
		$tpl = new Template("html/input");
		
		$tpl->vars["id"] = escapar($id);
		$tpl->vars["name"] = escapar($name);
		$tpl->vars["type"] = escapar($type);
		$tpl->vars["text"] = "";
		//$text ya llega escapado.
		if($text !== ""){
			$tpllabel = new Template("html/label");
			$tpllabel->vars["id"] = escapar($id);
			$tpllabel->vars["text"] = $text;
			$tpl->vars["text"] = $tpllabel->get();
		}
		if($type != "password"){
			$tpl->vars["value"] = escapar($value);
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
		return $this->textarea($name, $value, $onclick, $rows, $cols, $class, $text, self::idUnico($name));
	}
	
	//Como getTextarea, con el id ya reservado.
	private function textarea($name, $value, $onclick, $rows, $cols, $class, $text, $id){
		$tpl = new Template("html/textarea");
		$tpl->vars["id"] = escapar($id);
		$tpl->vars["name"] = escapar($name);
		$tpl->vars["value"] = escapar($value);
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
			$tplclick->vars["onclick"] = $onclick;
			$tpl->vars["onclick"] = $tplclick->get();
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
		if(!is_array($opciones)){
			$opciones = array();
		}
		$a_checked = array();
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
					$s_id = self::idUnico($name);
					$tpl->vars["title"] = $this->etiqueta($title,$s_id);
					$tpl->vars["textarea"] = $this->textarea($name,$value,$onclick,"","",$class,"",$s_id);			
					return $tpl->get();
				break;
			case "checkbox_single":
					$tpl = new Template("html/form_row");
					//if($value == 1){$checked = true;}
					$tpl->vars["input"] = $this->getInput($name,"checkbox",$value,$onclick,$class,$checked,"&nbsp;".escapar($title));
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
							$a_opciones[(string)@$opcion["name"]] = $opcion["id"];
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
					$tplgrupo = new Template("html/form_grupo_titulo");
					$s_id_grupo = self::idUnico($name."_grupo");
					$tplgrupo->vars["id"] = escapar($name);
					$tplgrupo->vars["id_grupo"] = escapar($s_id_grupo);
					$tplgrupo->vars["title"] = $title;
					$tpl->vars["title"] = $tplgrupo->get();
					$s_opciones = "";
					
					$n_opcion = 0;
					foreach($opciones as $option=>$valueo){
						$n_opcion++;
						if(is_array($valueo)){
							if(@$valueo["checked"]){
								$checked = true;
							}else{
								$checked = false;
							}
							$option = @$valueo["nombre"];
							$valueo = @$valueo["value"];
						}else{
							//null == 0 es true: sin valor no se marca ninguna opción.
							if($value !== null && $value == $valueo){
								$checked = true;
							}else{
								$checked = false;
							}
							if($valueo !== null && !empty($a_checked[$valueo])){
								$checked = true;
							}
						}
						
						
						/* !!! Metido para el caso de que sólo haya un elemento, hay que comprobar que no dé problemas....*/
						if(count($opciones) <= 1) $name_aux = $name;
						else $name_aux = $name."[]";
						/* !!! */
						
						
						$s_opciones .= $this->getInput($name_aux,$type,$valueo,$onclick,$class,$checked,"&nbsp;".escapar($option),$name."_".$n_opcion)."<br/>";
					}
					
					//Las opciones van en un grupo con el título como nombre.
					$tplgrupo = new Template("html/form_grupo");
					$tplgrupo->vars["id"] = escapar($name);
					$tplgrupo->vars["id_grupo"] = escapar($s_id_grupo);
					$tplgrupo->vars["input"] = $s_opciones;
					$tpl->vars["input"] = $tplgrupo->get();
					
					return $tpl->get();
				break;
			case "select":
			case "select_desactivado":
			case "select_dinamico":
			case "select_dinamico_desactivado":
			
					$tpl = new Template("html/select");
					$s_id = self::idUnico($name);
					$tpl->vars["id"] = escapar($s_id);
					$tpl->vars["name"] = escapar($name);
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
								$a_opciones[$opcion["id"]] = $opcion["name"];
							}else{
								$a_opciones[$opcion["id"]] = (string)@$opcion["nombre"];
							}
						}
						$opciones = $a_opciones;
					}
					
					foreach($opciones as $valueo=>$text){
						$tpl_aux = new Template("html/option");
						$tpl_aux->vars["name"] = escapar($text);
						$tpl_aux->vars["value"] = escapar($valueo);
						if($value !== null && $value == $valueo){
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
					$tpl_last->vars["title"] = $this->etiqueta($title,$s_id);
					$tpl_last->vars["input"] = $tpl->get();
					
					return $tpl_last->get();
				break;
			case "fecha":
					$class = "datepicker";
				break;
			case "password_cifrado":
					//Sin name ni value: lo envía cifrado js/cifrado.js (Page::prepararCifrado).
					$tpl = new Template("html/form_row");
					$s_id = self::idUnico($name);
					$tpl->vars["title"] = $this->etiqueta($title,$s_id);
					$tplinput = new Template("html/input_cifrado");
					$tplinput->vars["id"] = escapar($s_id);
					$tplinput->vars["name"] = escapar($name);
					$tplinput->vars["class"] = "";
					if($class != ""){
						$tplclass = new Template("html/class");
						$tplclass->vars["class"] = $class;
						$tplinput->vars["class"] = $tplclass->get();
					}
					$tpl->vars["input"] = $tplinput->get();
					return $tpl->get();
				break;
		}
		
		$tpl = new Template("html/form_row".$row_type);
		
		if($type != ''){
			$s_id = self::idUnico($name);
			//Las filas _colspan (ocultos y botones) no pintan el título.
			$tpl->vars["title"] = ($row_type === "") ? $this->etiqueta($title,$s_id) : $title;
			$tpl->vars["input"] = $this->input($name, $type, $value, $onclick, $class, false, "", $s_id);
		}else{
			$tpl->vars["title"] = '<p style="margin-top:25px;text-decoration: underline;">'.$title.'</p>';
			$tpl->vars["input"] = '<p style="height:50px;"></p>';
		}
		return $tpl->get();
	}
	
	
	//El título como etiqueta del campo con id $id; $title se pinta sin escapar, como hasta ahora.
	//Sin título o sin id (campo sin name) no hay campo al que asociarlo: se devuelve el título tal cual.
	private function etiqueta($title, $id){
		if($title == "" || $id == ""){
			return $title;
		}
		$tpllabel = new Template("html/label");
		$tpllabel->vars["id"] = escapar($id);
		$tpllabel->vars["text"] = $title;
		return $tpllabel->get();
	}
	
	
	function addContentTable($a_rows, $a_buttons = array(), $a_values = array()){
		$this->a_args_content = array($a_rows, $a_buttons, $a_values);
		
		$s_content = "";
		foreach($a_rows as $row){
			//En 8.5 una clave null da Deprecated también en isset: se normaliza antes de consultar.
			$s_nombre = isset($row["name"]) ? $row["name"] : "";
			$valor = isset($a_values[$s_nombre]) ? $a_values[$s_nombre] : "";
			if($valor != ""){
				if(is_array($valor)){
					$row["value"] = isset($valor[0]) ? $valor[0] : null;
				}else{
					$row["value"] = $valor;
				}
			}
			if(@$row["type"] == "checkbox_single" && $valor == 1){
				$row["checked"] = true;
			}
			$s_content .= $this->getRow(@$row["title"],@$row["name"],@$row["type"],@$row["value"],@$row["onclick"],"",@$row["class"],@$row["opciones"],@$row["checked"],@$row["tabla"]);
		}
		
		if(!is_array($a_buttons) || count($a_buttons) < 1){
			$a_buttons = $this->a_default_buttons;
		}
		
		foreach($a_buttons as $row){
			$type = "button";
			if((!isset($row["type"]) || $row["type"] == "") && (!isset($row["onclick"]) || $row["onclick"] == "")){
				$type = "submit";
			}
			$s_content .= $this->getRow("",@$row["name"],$type,@$row["value"],@$row["onclick"],"_colspan",@$row["class"]);
		}
		
		$tpl = new Template("html/form_main");
		$tpl->vars["content"] = $s_content;
		
		$this->s_content = $tpl->get();
		$this->b_content_pintado = false;
		$this->vars["content"] = $this->s_content;
	}
	
	
	//Devuelve $s_base o, si ya está usado en la petición, el primero libre de <base>_2, <base>_3..., y lo anota.
	//Sin base (campo sin name) no hay id que reservar. Sin escapar: quien lo pinte lo escapa.
	public static function idUnico($s_base){
		$s_base = is_scalar($s_base) ? (string)$s_base : "";
		if($s_base === ""){
			return "";
		}
		if(self::$a_ids === null){
			self::$a_ids = self::idsPlantillas();
		}
		$s_id = $s_base;
		//Solo dígitos cuenta como usado: las filas del listado usan el id de la BD.
		for($i = 2; isset(self::$a_ids[$s_id]) || ctype_digit($s_id); $i++){
			$s_id = $s_base."_".$i;
		}
		self::$a_ids[$s_id] = true;
		return $s_id;
	}
	
	
	//Anota un id que pinta la página, tal cual, para que los que genere Form después cedan.
	//false si ya estaba usado o no es válido (vacío o solo dígitos); no renombra lo ya generado.
	public static function reservarId($s_id){
		if(!is_scalar($s_id)){
			return false;
		}
		$s_id = (string)$s_id;
		if($s_id === "" || ctype_digit($s_id)){
			return false;
		}
		if(self::$a_ids === null){
			self::$a_ids = self::idsPlantillas();
		}
		if(isset(self::$a_ids[$s_id])){
			return false;
		}
		self::$a_ids[$s_id] = true;
		return true;
	}
	
	
	//Ids literales (sin {variables}) de todas las plantillas de general y specific, se pinten o no: Form nunca los usa.
	private static function idsPlantillas(){
		$a_ids = array();
		$conf = new AppConf();
		foreach(array("cfw/general/templates", "cfw/specific/templates") as $s_carpeta){
			$s_ruta = $conf->config["pathbase"].$s_carpeta;
			if(!is_dir($s_ruta)){
				continue;
			}
			try{
				//Sin FOLLOW_SYMLINKS no entra en carpetas enlazadas; CATCH_GET_CHILD salta las que no se pueden leer.
				$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($s_ruta, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);
				foreach($it as $fichero){
					if($fichero->isLink() || !$fichero->isFile() || $fichero->getExtension() !== "tpl"){
						continue;
					}
					$s_plantilla = @file_get_contents($fichero->getPathname());
					if($s_plantilla === false){
						continue;
					}
					if(preg_match_all('#\bid=("[^"{]*"|\'[^\'{]*\')#', $s_plantilla, $a_coincidencias)){
						foreach($a_coincidencias[1] as $s_id){
							$a_ids[substr($s_id, 1, -1)] = true;
						}
					}
				}
			}catch(Exception $e){
				continue;
			}
		}
		return $a_ids;
	}
	
}//class
