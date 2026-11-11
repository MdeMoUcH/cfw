<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 
 * 2010
 * 
 ***************/
 
class AdminHelper extends Page implements PaginaAdmin{
	
	public $last_file_name = "";
	protected $slug = "admin";
	protected $extensiones_permitidas = array("tpl","css");
	protected $s_error_subida = "";
	protected $a_subidas_guardadas = array();
	//Unos 80 MB al decodificar: cabe en un memory_limit de 128M.
	protected $img_max_pixeles = 16000000;
	//Las define cada subclase.
	protected $class;
	protected $titulo;
	protected $template;
	protected $singular;
	protected $paginacion;
	protected $template_listado;
	protected $template_single;
	protected $slug_admin;
	protected $categorias;
	protected $categorias_orm;
	protected $config_name;
	
	
	function __construct(){
		parent::__construct();
		
		$this->exigirAdmin();
		
		if(@$this->slug_admin != ""){
			$this->vars["menu"] = $this->get_menu($this->slug_admin);
		}else{
			$this->vars["menu"] = $this->get_menu($this->slug);
		}
	}
	
	public function pag($pag = 1){
		$this->init($pag);
	}
	
	public function init($pag = 1){
		
		if(isset($this->template_listado) && $this->template_listado != ""){
			$this->template = $this->template_listado;
		}
		
		$tpl = $this->load_template();
		
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		$tpl->vars["titulo"] = $this->titulo;
		$tpl->vars["slug"] = $this->slug;
		
		$orm = new $this->class();
		//$elements = $orm->getAllPAginado($pag,$this->paginacion);
		$elements = $orm->getAll();
		
		if($elements){
			$tpl->vars["content"] = "";
			$i=0;
			foreach($elements as $element){
				$tplelement = new Template("element_listado_pro");
				$tplelement->vars["urlbase"] = $this->config["urlbase"];
				$tplelement->vars["slug"] = $this->slug;
				//Sin nombre ni name (como en Usuarios), el email.
				if(@$element["nombre"] != ""){
					$s_etiqueta = $element["nombre"];
				}elseif(@$element["name"] != ""){
					$s_etiqueta = $element["name"];
				}else{
					$s_etiqueta = (string)@$element["email"];
				}
				$tplelement->vars["titulo"] = escapar($s_etiqueta);
				$tplelement->vars["nombre"] = escapar($s_etiqueta);
				$tplelement->vars["id"] = $element["id"];
				$aux_id = $element["id"];
				$aux_name = "elementos[]";
				
				$form = new Form();				
				$tplelement->vars["input"] = $form->getInput($aux_name,"checkbox",$aux_id);
				
				$tpl->vars["content"] .= $tplelement->get();
				$i++;
			}
			
			$tpl_aux = new Template("listado_pro");
			$tpl_aux->vars["confirmar_borrado_json"] = json_js(t("text_confirmar_borrado"));
			$tpl_aux->vars["editfield_url_json"] = json_js($this->config["urlbase"]."admin/".$this->slug."/editfield");
			$tpl_aux->vars["error_edicion_json"] = json_js(t("text_hubo_error"));
			$tpl_aux->vars["elementos"] = $tpl->vars["content"];
			$tpl->vars["content"] = $tpl_aux->get();
			
			$this->add_include("js",$this->config["urlbase"]."js/prototype-1.7.3.js");
			$this->add_include("js",$this->config["urlbase"]."js/tablekit.js");
		}else{
			$tpl->vars["content"] = $this->titulo;
			$tpl->vars["content"] = t("text_no_hay_elemento",array("elemento" => $this->singular));
		}
		
		$orm->cerrar();
		
		$this->show($tpl);
	}
	
	
	public function ver($id = ""){
		//$this->vars["menu"] = $this->get_menu($this->slug);
		
		$tpl = $this->load_template();
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		$tpl->vars["titulo"] = $this->titulo;
		$tpl->vars["slug"] = $this->slug;
		
		$orm = new $this->class();
		//$element = @$orm->getBySlug($id);
		$element = ($id != "") ? @$orm->getById($id) : false;
		if($element){
			$tplelement = $this->load_template_single();
			
			foreach($element as $campo=>$valor){
				$tplelement->vars[$campo] = $valor;
			}
			
			$tpl->vars["titulo"] = isset($element["titulo"]) ? $element["titulo"] : $this->titulo;
			$tpl->vars["content"] = $tplelement->get();
			
			if($this->session->isUserAdmin()){
				$tpleditar = new Template("element_editar");
				$tpleditar->vars["urlbase"] = $this->config["urlbase"];
				$tpleditar->vars["slug"] = $this->slug;
				$tpleditar->vars["id"] = $element["id"];
				
				$tplp = new Template("html/p");
				$tplp->vars["text"] = $tpleditar->get();
				$tpl->vars["content"] .= "<p><a href='".$this->config["urlbase"]."admin/".$this->slug."/edit/".$element["id"]."'>".t("text_editar")."</a></p>";
			}		
		}else{
			$tpl->vars["content"] = $this->titulo;
			$tpl->vars["content"] = t("text_elemento_no_encontrado",array("elemento" => $this->singular));
		}
		$orm->cerrar();
		
		$this->show($tpl);
	}
	
	
		


	public function form($save = "", $id = "",$s_msg = "", $a_buttons = array()){
		
		$b_insert_error = false;
		
		$orm = new $this->class();
		
		$tplmsg = new Template("html/p_color");
		
		if($save != "" && @$_SERVER["REQUEST_METHOD"] === "POST"){
			if($orm->insert($_POST)){
				$id_aux = mysqli_insert_id($orm->conexion);
				if($id_aux){
					$id = $id_aux;
				}
				
				$tplmsg->vars["text"] = "";
				$this->a_subidas_guardadas = array();
				
				if($this->guardarSubidas($orm,$id,$tplmsg)){
					$tplmsg->vars["color"] = "Green";
					$tplmsg->vars["text"] = t("text_elemento_insertado",array("elemento" => $this->singular));
					
					if(@$this->categorias && isset($_POST["categorias"])){
						$this->guardarCategorias($id,$_POST["categorias"]);
					}
				}else{
					$this->deshacerAlta($orm,$id_aux,$tplmsg);
					$b_insert_error = true;
					$id = "";
				}
				
				$s_msg = $tplmsg->get();
			}else{
				$b_insert_error = true;
				$tplmsg->vars["text"] = "";
				
				foreach($orm->errores as $error){
					$tplmsg->vars["color"] = "Red";
					$tplmsg->vars["text"] .= $error;
					
					$s_msg .= $tplmsg->get();
				}
			}
		}
		
		$s_action = "form/save";
		$a_values = array();
		if($id != ""){
			if($orm->getByIdAdmin($id)){
				$a_values = $orm->resultado[0];
				
				//$orm->campos_form["id"] = array("title" => "","name" => "id","type" => "hidden");
				$orm->campos_form[@$orm->primarykey] = array("title" => "","name" => "id","type" => "hidden");
				//$orm->campos_form["borrado"] = array("title" => "Borrado","name" => "borrado","type" => "radio", "opciones" => array("Si" => "1", "No" => "0"));
				$s_action = "edit/".$id."/save";
			}
		}else{
			if(count($_POST) >= 1 && $b_insert_error){
				$a_values = $_POST;
			}
			if(@$orm->campos["fk_id_usuario"] != ""){
				$orm->campos_form["fk_id_usuario"] = array("title" => "","name" => "fk_id_usuario","type" => "hidden", "value" => $this->session->data["id_usuario"]);
			}
		}
		
		$orm->cerrar();
		
		foreach((array)$orm->campos_form as $campo){
			if(!isset($campo["name"])){
				continue;
			}
			if(@$campo["type"] == "select_dinamico" || @$campo["type"] == "select_dinamico_desactivado"){
					$orm_aux = new DinamicOrm();
					$orm_aux->setTabla($campo["tabla"]);
					$orm_aux->getAll();
					$a_opciones = array();
					foreach($orm_aux->resultado as $opcion){
						if(@$opcion["name"] != ""){
							$a_opciones[$opcion["id"]] = $opcion["name"];
						}else{
							$a_opciones[$opcion["id"]] = (string)@$opcion["nombre"];
						}
					}
					//$orm->campos_form[$campo["name"]]["type"] = "select";
					$orm->campos_form[$campo["name"]]["opciones"] = $a_opciones;
			}elseif(@$campo["type"] == "checkbox_single"){
				$valor_actual = isset($a_values[$campo["name"]]) ? $a_values[$campo["name"]] : null;
				$valor_campo = isset($orm->campos_form[$campo["name"]]["value"]) ? $orm->campos_form[$campo["name"]]["value"] : null;
				if($valor_actual == $valor_campo){
					$orm->campos_form[$campo["name"]]["checked"] = true;
				}
			}
		}
		
		$a_campos = array();
		$a_campos = (array)$orm->campos_form;
		
		if(@$this->categorias){
			$a_cat_values = array();
			
			if($id != ""){
				$cat = new $this->categorias_orm();
				if($cat->getCatById($id)){
					
					foreach($cat->resultado as $categoria){
						$a_cat_values[$categoria["fk_id_categoria"]] = $categoria["fk_id_categoria"];
					}
				}
			}
			
			$a_campos["categorias"] = array("title" => t("text_categorias"),
											"name" => "categorias",
											"type" => "checkbox_dinamico",
											"tabla" => $this->categorias,
											"opciones" => $a_cat_values);
		}
		
		$form = new Form();
		
		$form->vars["name"] = "formulario";
		$form->setAction($this->config["urlbase"]."admin/".$this->slug."/".$s_action);
		$form->setMethod("POST");
		
		$form->addContentTable($a_campos,$a_buttons,$a_values);
		
		//$this->vars["menu"] = $this->get_menu($this->slug);
		
		$tpl = $this->load_template();
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		$tpl->vars["titulo"] = t("text_titulo_form",array("titulo" => $this->titulo));
		$tpl->vars["slug"] = $this->slug;
		
		$tpl->vars["content"] = $s_msg.$form->getForm();
		
		$this->show($tpl);
	}
	
	
	
	public function edit($id, $save = "", $a_buttons = array()){
		
		$s_msg = "";
		if($save != "" && @$_SERVER["REQUEST_METHOD"] === "POST"){
			$orm = new $this->class();
			$tplmsg = new Template("html/p_color");
			
			if(isset($orm->checkbox_singles) && count($orm->checkbox_singles) >= 1){
				foreach($orm->checkbox_singles as $campo){
					$this->niapa_checkboxes($campo);
				}
			}
			if($orm->update($_POST,$id)){
				$tplmsg->vars["color"] = "Green";
				$tplmsg->vars["text"] = t("text_elemento_actualizado",array("Elemento" => ucfirst((string)$this->singular)));
					
				$this->guardarSubidas($orm,$id,$tplmsg);
				
				$s_msg = $tplmsg->get();
				
				if(@$this->categorias){
					if(isset($_POST["categorias"])){
						$this->guardarCategorias($id,$_POST["categorias"]);
					}
				}
			}else{
				
				$tplmsg->vars["text"] = "";
				
				foreach($orm->errores as $error){
					$tplmsg->vars["color"] = "Red";
					$tplmsg->vars["text"] = $error;
					
					$s_msg .= $tplmsg->get();
				}
			}
		}
		
		$this->form("",$id,$s_msg,$a_buttons);
	}
	
	
	function delete($id){
		if(@$_SERVER["REQUEST_METHOD"] !== "POST"){
			http_response_code(405);
			header("Allow: POST");
			$this->showError(t("text_borrado_solo_post"));
			//El exit corta la petición aunque una subclase redefina showError sin terminar.
			exit;
		}
		
		//$this->vars["menu"] = $this->get_menu($this->slug);
		$tpl = new Template("main");
		$tpl->vars["titulo"] = t("text_borrado_de",array("titulo" => $this->titulo));
		
		$orm = new $this->class();
		if($orm->delete($id)){
			$tpl->vars["content"] = t("text_borrado_ok",array("elemento" => $this->singular, "id" => escapar($id)))."<br/><a href='javascript:window.history.back();'>".t("text_volver")."</a>";
		}else{
			$tpl->vars["content"] = t("text_borrado_ko",array("elemento" => $this->singular, "id" => escapar($id)))."<br/><a href='javascript:window.history.back();'>".t("text_volver")."</a>";
		}
		
		
		$this->show($tpl);
	}
	
	
	
	
	
	function editfield(){
		//Como en delete: un PUT o DELETE no pasa por comprobar_csrf.
		if(@$_SERVER["REQUEST_METHOD"] !== "POST"){
			http_response_code(405);
			header("Allow: POST");
			$this->showError(t("text_editfield_solo_post"));
			exit;
		}
		
		//Los exit cortan la petición aunque una subclase redefina rechazarEdicion sin terminar.
		$s_campo = @$_POST["field"];
		$valor = @$_POST["value"];
		$id = @$_POST["id"];
		//La contraseña solo se cambia desde un formulario que la envíe cifrada (Page::leerPassCifradas); TableKit no puede.
		if(!is_string($s_campo) || $s_campo === "pass"){
			$this->rechazarEdicion(t("text_campo_no_valido"));
			exit;
		}
		if(!is_string($valor)){
			$this->rechazarEdicion(t("text_valor_no_valido"));
			exit;
		}
		if(!is_string($id) || !ctype_digit($id)){
			$this->rechazarEdicion(t("text_id_no_valido"));
			exit;
		}
		
		$orm = new $this->class();
		if(!in_array($s_campo,$this->camposEditables($orm),true)){
			$orm->cerrar();
			$this->rechazarEdicion(t("text_campo_no_valido"));
			exit;
		}
		//Lo que niapa_checkboxes deja en el formulario.
		if(in_array($s_campo,(array)$orm->checkbox_singles,true) && $valor !== "0" && $valor !== "1"){
			$orm->cerrar();
			$this->rechazarEdicion(t("text_valor_no_valido"));
			exit;
		}
		
		//Solo esa columna y por update, como edit: la fila entera volvería a filtrar los valores guardados.
		$b_ok = $orm->update(array($s_campo => $valor),$id);
		$a_errores = (array)$orm->errores;
		$orm->cerrar();
		if($b_ok){
			header("Content-Type: text/plain; charset=UTF-8");
			die(escapar($valor));
		}
		$this->rechazarEdicion(count($a_errores) >= 1 ? reset($a_errores) : t("text_hubo_error"));
		exit;
	}
	
	
	//Columnas que puede escribir editfield: las del formulario, sin las que este no edita como texto u opción.
	protected function camposEditables($orm){
		$a_excluidos = array("hidden","img","file","password","password_cifrado","cabecera","checkbox","checkbox_dinamico","select_desactivado","select_dinamico_desactivado");
		$a_editables = array();
		foreach((array)$orm->campos_form as $a_campo){
			if(!is_array($a_campo) || !isset($a_campo["name"]) || !is_string($a_campo["name"])){
				continue;
			}
			$s_nombre = $a_campo["name"];
			$s_tipo = (isset($a_campo["type"]) && is_string($a_campo["type"])) ? $a_campo["type"] : "";
			if(isset($orm->campos[$s_nombre]) && $s_nombre !== "id" && $s_nombre !== $orm->primarykey && $s_nombre !== "pass" && !in_array($s_tipo,$a_excluidos,true)){
				$a_editables[] = $s_nombre;
			}
		}
		return $a_editables;
	}
	
	
	//Responde 400 con el motivo escapado. Protegido para que lo use un editfield redefinido.
	protected function rechazarEdicion($s_texto){
		http_response_code(400);
		header("Content-Type: text/plain; charset=UTF-8");
		die(escapar($s_texto));
	}
	
	
	
	protected function load_template(){
		if(file_exists($this->config["pathbase"]."cfw/general/templates/".$this->template.".tpl") || 
			file_exists($this->config["pathbase"]."cfw/specific/templates/".$this->template.".tpl")){
			$tpl = new Template($this->template);
		}else{
			$tpl = new Template("elements");
		}
		return $tpl;
	}
	
	private function load_template_single(){
		if(file_exists($this->config["pathbase"]."cfw/general/templates/".$this->slug.".tpl") || 
			file_exists($this->config["pathbase"]."cfw/specific/templates/".$this->slug.".tpl")){
			$tpl = new Template($this->template_single != "" ? $this->template_single : $this->slug);
		}else{
			$tpl = new Template("element");
		}	
		return $tpl;
	}
	
	private function niapa_checkboxes($txt){
		if(isset($_POST[$txt])){
			$_POST[$txt] = 1;
		}else{
			$_POST[$txt] = 0;
		}
	}
	
		
		
	//Sube los archivos de $_FILES. El tipo se decide por el contenido, nunca por el MIME del cliente.
	protected function guardarSubidas($orm,$id,$tplmsg){
		$b_todo_ok = true;
		foreach($_FILES as $name=>$file){
			if($file["error"] === UPLOAD_ERR_NO_FILE){
				continue;
			}
			$b_ok = false;
			if(!is_string($file["name"]) || $file["name"] == "" || $file["error"] !== UPLOAD_ERR_OK){
				$this->s_error_subida = t("text_subida_no_recibido");
			}elseif(isset($orm->campos[$name]) && is_uploaded_file($file["tmp_name"])){
				$a_datos = @getimagesize($file["tmp_name"]);
				if($a_datos !== false && in_array($a_datos[2],array(1,2,3))){
					$b_ok = $this->saveImage($file,$id);
				}else{
					$b_ok = $this->saveFile($file,$id);
				}
				if($b_ok && !$orm->updateField($id,$name,$this->last_file_name)){
					$b_ok = false;
					$this->s_error_subida = t("text_subida_dato");
				}
			}else{
				$this->s_error_subida = t("text_subida_campo");
			}
			if(!$b_ok){
				$b_todo_ok = false;
				$tplmsg->vars["color"] = "Red";
				if(!isset($tplmsg->vars["text"])){
					$tplmsg->vars["text"] = "";
				}
				$tplmsg->vars["text"] .= " ".t("text_subida_problema",array("campo" => escapar($name), "error" => $this->s_error_subida));
			}
		}//foreach
		return $b_todo_ok;
	}
	
	
	//Solo con el id que devolvió este INSERT: el id de la URL es de otra fila.
	protected function deshacerAlta($orm,$id_alta,$tplmsg){
		//Sin id propio, el nombre del archivo podría ser de otra fila.
		if($id_alta){
			foreach($this->a_subidas_guardadas as $s_archivo){
				@unlink($s_archivo);
			}
		}
		
		if($id_alta && $orm->eliminarFila($id_alta)){
			$tplmsg->vars["text"] = t("text_alta_deshecha",array("elemento" => $this->singular)).$tplmsg->vars["text"];
		}else{
			error_log("CFW: no se pudo deshacer el alta en ".$orm->tabla." (id: ".(int)$id_alta.")");
			$tplmsg->vars["text"] = t("text_alta_no_deshecha",array("elemento" => $this->singular)).$tplmsg->vars["text"];
		}
	}
	
	
	protected function rutaSubida($s_clave){
		return isset($this->config_name) ? @$this->config[$this->config_name.$s_clave] : "";
	}
	
	
	protected function nombreSubida($s_nombre){
		return preg_replace('/[^A-Za-z0-9_-]/','_',pathinfo(basename((string)$s_nombre),PATHINFO_FILENAME));
	}
	
	
	protected function saveImage($image_data, $s_nombre = "", $anchura = "", $hmax = "")
	{
		$b_ok = false;
		$this->s_error_subida = "";

		//Primero el contenido: así el rechazo de un archivo que no es imagen no depende de GD.
		$s_image = $image_data["tmp_name"];
		//Con una ruta vacía, getimagesize lanza ValueError y @ no lo silencia.
		$a_datos = (is_string($s_image) && $s_image != "") ? @getimagesize($s_image) : false;
		
		if($a_datos === false || !in_array($a_datos[2],array(1,2,3)) || $a_datos[0] < 1 || $a_datos[1] < 1)
		{
			$this->s_error_subida = t("text_img_no_admitida");
			return false;
		}//if
		
		//Las dimensiones vienen de la cabecera: decodificar una imagen enorme agotaría la memoria con un fatal.
		if($a_datos[0] * $a_datos[1] > $this->img_max_pixeles)
		{
			$this->s_error_subida = t("text_img_grande");
			return false;
		}//if
		
		if(!function_exists("imagecreatetruecolor"))
		{
			error_log("CFW: saveImage necesita la extensión GD");
			$this->s_error_subida = t("text_img_sin_gd");
			return false;
		}//if
		
		//GD puede estar compilada sin algún formato, y llamar a la función daría un fatal.
		$a_funciones = array(1 => array("imagecreatefromgif","imagegif"), 2 => array("imagecreatefromjpeg","imagejpeg"), 3 => array("imagecreatefrompng","imagepng"));
		if(!function_exists($a_funciones[$a_datos[2]][0]) || !function_exists($a_funciones[$a_datos[2]][1]))
		{
			error_log("CFW: GD no admite el tipo de imagen ".$a_datos[2]);
			$this->s_error_subida = t("text_img_tipo");
			return false;
		}//if

		if($anchura == "")
		{
			$anchura = $this->rutaSubida(".img.width");
		}//if

		if($hmax == "")
		{
			$hmax = $this->rutaSubida(".img.height");
		}//if
		
		$s_ruta = $this->rutaSubida(".img.path");
		
		if(!is_numeric($anchura) || !is_numeric($hmax) || $anchura <= 0 || $hmax <= 0 || $s_ruta == "")
		{
			error_log("CFW: saveImage sin configuración de tamaño o ruta");
			$this->s_error_subida = t("text_subida_config");
			return false;
		}//if
		
		//La extensión sale siempre del tipo real, nunca del nombre enviado.
		$s_nombre = $this->nombreSubida($s_nombre != "" ? $s_nombre : $image_data["name"]);
		
		if($s_nombre == "")
		{
			$this->s_error_subida = t("text_subida_nombre");
			return false;
		}//if

		if($a_datos[2] == 1)
		{
			$img = @imagecreatefromgif($s_image);
			$s_nombre .= ".gif";
		}//if
		if($a_datos[2] == 2)
		{
			$img = @imagecreatefromjpeg($s_image);
			$s_nombre .= ".jpg";
		}//if
		if($a_datos[2] == 3)
		{
			$img = @imagecreatefrompng($s_image);
			$s_nombre .= ".png";
		}//if
		
		if(!$img)
		{
			$this->s_error_subida = t("text_img_no_admitida");
			return false;
		}//if

		$ratio = ($a_datos[0] / $anchura);
		$altura = ($a_datos[1] / $ratio);
		if($altura > $hmax)
		{
			$anchura2 = $hmax * $anchura / $altura;
			$altura = $hmax;
			$anchura = $anchura2;
		}//if
		$anchura = max(1,(int)$anchura);
		$altura = max(1,(int)$altura);

		$thumb = imagecreatetruecolor($anchura,$altura);
		if(!$thumb)
		{
			$this->s_error_subida = t("text_img_proceso");
			return false;
		}//if
		imagecopyresampled($thumb, $img, 0, 0, 0, 0, $anchura, $altura, $a_datos[0], $a_datos[1]);


		//Marca de agua
		/*
		$forrest_green = imagecolorallocate($thumb, 34, 139, 34);
		$white = imagecolorallocate($thumb, 255, 255, 255);
		$black = imagecolorallocate($thumb, 0, 0, 0);
		if($anchura <= 180)
		{
			imagestring($thumb, 1, ($anchura-84), ($altura-10),  $this->config["name"], $white);
		}//if
		elseif($anchura <= 360)
		{
			imagestring($thumb, 24, ($anchura-180), ($altura-12),  $this->config["name"], $black);
		}//elseif
		else
		{
			imagestring($thumb, 24, ($anchura-360), ($altura-15),  $this->config["name"], $forrest_green);
		}//else
		*/
		//Marca de agua


		if($a_datos[2] == 1)
		{
			//header("Content-type: image/gif");
			$b_ok = @imagegif($thumb,$s_ruta.$s_nombre);
		}
		if($a_datos[2] == 2)
		{
			//header("Content-type: image/jpeg");
			$b_ok = @imagejpeg($thumb,$s_ruta.$s_nombre);
		}//if
		if($a_datos[2] == 3)
		{
			//header("Content-type: image/png");
			$b_ok = @imagepng($thumb,$s_ruta.$s_nombre);
		}//if

		if(!$b_ok)
		{
			error_log("CFW: no se pudo escribir ".$s_ruta.$s_nombre);
			$this->s_error_subida = t("text_subida_guardar");
			return false;
		}//if
		
		$this->last_file_name = $s_nombre;
		$this->a_subidas_guardadas[] = $s_ruta.$s_nombre;

		return true;
	}//fun
	
		
		
	protected function saveFile($file_data, $s_nombre = "")
	{	
		$this->s_error_subida = "";
		
		if($s_nombre == "")
		{
			$s_nombre = $file_data["name"];
			$s_ext = strtolower(pathinfo(basename($s_nombre),PATHINFO_EXTENSION));
		}//if
		elseif($file_data["type"] == "application/octet-stream" || $file_data["type"] == "text/html")
		{
			$s_ext = "tpl";
		}//elseif
		elseif($file_data["type"] == "text/css")
		{
			$s_ext = "css";
		}//elseif
		else
		{
			$s_ext = "";
		}//else
		
		if(!in_array($s_ext,$this->extensiones_permitidas))
		{
			$this->s_error_subida = t("text_subida_tipo");
			return false;
		}//if
		
		//Con una ruta vacía, file_get_contents lanza ValueError y @ no lo silencia.
		if(!is_string($file_data["tmp_name"]) || $file_data["tmp_name"] == "")
		{
			$this->s_error_subida = t("text_subida_no_recibido");
			return false;
		}//if
		
		if(strpos((string)@file_get_contents($file_data["tmp_name"]),"<?") !== false)
		{
			$this->s_error_subida = t("text_subida_contenido");
			return false;
		}//if
		
		$s_ruta = $this->rutaSubida(".path");
		$s_nombre = $this->nombreSubida($s_nombre);
		
		if($s_ruta == "" || $s_nombre == "")
		{
			$this->s_error_subida = t("text_subida_config_nombre");
			return false;
		}//if
		
		$s_nombre .= ".".$s_ext;
		if(!@copy($file_data["tmp_name"],$s_ruta.$s_nombre))
		{
			error_log("CFW: no se pudo escribir ".$s_ruta.$s_nombre);
			$this->s_error_subida = t("text_subida_guardar");
			return false;
		}//if
		
		$this->last_file_name = $s_nombre;
		$this->a_subidas_guardadas[] = $s_ruta.$s_nombre;
		return true;
	}//fun
	
	
	protected function guardarCategorias($id,$a_data){
		$cat = new $this->categorias_orm();
		
		$cat->limpiarCategorias($id);
		
		foreach((array)$a_data as $categoria){
			$cat->addCat($id,$categoria);
		}
	}
	
}//class
