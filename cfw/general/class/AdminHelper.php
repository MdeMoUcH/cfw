<?
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 
 * 2010
 * 
 ***************/
 
class AdminHelper extends Page{
	
	public $last_file_name = "";
	protected $slug = "admin";
	protected $jhtmlarea = false;
	
	
	function __construct(){
		parent::__construct();
		
		if(!$this->session->isUserAdmin()){
			header("Location: ".$this->config["urlbase"]);
		}
		
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
				if($this->slug == "users"){
					$element["nombre"] = $element["email"];
				}
				$tplelement = new Template("element_listado_pro");
				$tplelement->vars["urlbase"] = $this->config["urlbase"];
				$tplelement->vars["slug"] = $this->slug;
				$tplelement->vars["titulo"] = $element["nombre"];
				$tplelement->vars["nombre"] = $element["nombre"];
				$tplelement->vars["id"] = $element["id"];
				$aux_id = $element["id"];
				$aux_name = "elementos[]";
				
				$form = new Form();				
				$tplelement->vars["input"] = $form->getInput($aux_name,"checkbox",$aux_id);
				
				$tpl->vars["content"] .= $tplelement->get();
				$i++;
			}
			
			$tpl_aux = new Template("listado_pro");
			$tpl_aux->vars["elementos"] = $tpl->vars["content"];
			$tpl->vars["content"] = $tpl_aux->get();
			
			$this->add_include("js",$this->config["urlbase"]."js/prototype.js");
			//$this->add_include("js",$this->config["urlbase"]."js/fabtabulous.js");
			$this->add_include("js",$this->config["urlbase"]."js/tablekit.js");
			
			$this->add_include("css",$this->config["urlbase"]."css/tablas.css");
		}else{
			$tpl->vars["content"] = $this->titulo;
			$tpl->vars["content"] = "No hay ningún ".$this->singular.".";
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
		if($id != ""){
			//$element = @$orm->getBySlug($id);
			$element = @$orm->getById($id);
			
			$tplelement = $this->load_template_single();
			
			foreach($element as $campo=>$valor){
				$tplelement->vars[$campo] = $valor;
			}
			
			$tpl->vars["titulo"] = $element["titulo"];
			$tpl->vars["content"] = $tplelement->get();
			
			if($this->session->isUserAdmin()){
				$tpleditar = new Template("element_editar");
				$tpleditar->vars["urlbase"] = $this->config["urlbase"];
				$tpleditar->vars["slug"] = $this->slug;
				$tpleditar->vars["id"] = $element["id"];
				
				$tplp = new Template("html/p");
				$tplp->vars["text"] = $tpleditar->get();
				$tpl->vars["content"] .= "<p><a href='".$this->config["urlbase"]."admin/".$this->slug."/edit/".$element["id"]."'>Editar</a></p>";
			}		
		}else{
			$tpl->vars["content"] = $this->titulo;
			$tpl->vars["content"] = "No se encontró el ".$this->singular.".";
		}
		$orm->cerrar();
		
		$this->show($tpl);
	}
	
	
		


	public function form($save = "", $id = "",$s_msg = "", $a_buttons = array()){
		
		//$this->add_include("js",$this->config["urlbase"]."js/color/jscolor.js");
		
		$b_insert_error = false;
		
		$orm = new $this->class();
		
		$tplmsg = new Template("html/p_color");
		
		if($save != ""){
			if($orm->insert($_POST)){
				$tplmsg->vars["color"] = "Green";
				$tplmsg->vars["text"] = "OK - ".$this->singular." insertado.";
				
				$s_msg = $tplmsg->get();
				$id_aux = mysqli_insert_id($orm->conexion);
				if($id_aux){
					$id = $id_aux;
				}
				
				//guardado de imágenes y archivos
				if(count($_FILES) >= 1){
					//muere($this,1);
					foreach($_FILES as $name=>$file){
						if($file["name"] != "" && $file["error"] == 0){
							if(strpos($file["type"],"image") !== false){
								if($this->saveImage($file,$id)){
									$orm->updateField($id,$name,$this->last_file_name);
								}else{
									$tplmsg->vars["text"] .= "Hubo algún problema al subir la imágen de ".$name.".";
								}
							}else{
								if($this->saveFile($file,$id)){
									$orm->updateField($id,$name,$this->last_file_name);	
								}else{
									$tplmsg->vars["text"] .= "Hubo algún problema al subir el archivo de ".$name.".";
								}
							}
						}
					}//foreach
				}
				
				if(@$this->categorias){
					$this->guardarCategorias($id,$_POST["categorias"]);
				}
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
		
		foreach($orm->campos_form as $campo){
			if($campo["type"] == "select_dinamico" || $campo["type"] == "select_dinamico_desactivado"){
					$orm_aux = new DinamicOrm();
					$orm_aux->setTabla($campo["tabla"]);
					$orm_aux->getAll();
					$a_opciones = array();
					foreach($orm_aux->resultado as $opcion){
						$a_opciones[$opcion["id"]] = $opcion["name"];
					}
					//$orm->campos_form[$campo["name"]]["type"] = "select";
					$orm->campos_form[$campo["name"]]["opciones"] = $a_opciones;
			}elseif($campo["type"] == "checkbox_single"){
				if(@$a_values[$campo["name"]] == $orm->campos_form[$campo["name"]]["value"]){
					$orm->campos_form[$campo["name"]]["checked"] = true;
				}
			}
		}
		
		$a_campos = array();
		$a_campos = $orm->campos_form;
		
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
			
			$a_campos["categorias"] = array("title" => "Categorías",
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
		$tpl->vars["titulo"] = $this->titulo." Form";
		$tpl->vars["slug"] = $this->slug;
		
		$tpl->vars["content"] = $s_msg.$form->getForm();
		
		$this->show($tpl);
	}
	
	
	
	public function edit($id, $save = "", $a_buttons = array()){
		
		//$this->add_include("js",$this->config["urlbase"]."js/color/jscolor.js");
		
		if(!$this->session->isUserAdmin()){
			//header("Location: ".$this->config["urlbase"]."login");
		}
		$s_msg = "";
		if($save != ""){
			$orm = new $this->class();
			$tplmsg = new Template("html/p_color");
			
			if(isset($orm->checkbox_singles) && count($orm->checkbox_singles) >= 1){
				foreach($orm->checkbox_singles as $campo){
					$this->niapa_checkboxes($campo);
				}
			}
			if($orm->update($_POST)){
				$tplmsg->vars["color"] = "Green";
				$tplmsg->vars["text"] = ucfirst($this->singular)." actualizado con éxito.";
					
				$s_msg = $tplmsg->get();
				
				//guardado de imágenes y archivos
				if(count($_FILES) >= 1){
					//muere($this,1);
					foreach($_FILES as $name=>$file){
						if($file["name"] != "" && $file["error"] == 0){
							if(strpos($file["type"],"image") !== false){
								if($this->saveImage($file,$id)){
									$orm->updateField($id,$name,$this->last_file_name);
								}else{
									$tplmsg->vars["text"] .= "Hubo algún problema al subir la imágen de ".$name.".";
								}
							}else{
								if($this->saveFile($file,$id)){
									$orm->updateField($id,$name,$this->last_file_name);	
								}else{
									$tplmsg->vars["text"] .= "Hubo algún problema al subir el archivo de ".$name.".";
								}
							}
						}
					}//foreach
				}
				
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
		//$this->vars["menu"] = $this->get_menu($this->slug);
		$tpl = new Template("main");
		$tpl->vars["titulo"] = "Borrado de ".$this->titulo;
		
		$orm = new $this->class();
		if($orm->delete($id)){
			$tpl->vars["content"] = $this->singular." con id ".$id." se borrado correctamente.<br/><a href='javascript:window.history.back();'>Volver</a>";
		}else{
			$tpl->vars["content"] = $this->singular." con id ".$id." no se borró correctamente.<br/><a href='javascript:window.history.back();'>Volver</a>";
		}
		
		
		$this->show($tpl);
	}
	
	
	
	
	
	function editfield(){
		$orm = new $this->class();
		if($orm->updateField($_POST["id"],$_POST["field"],$_POST["value"])){
			die($_POST["value"]);
		}else{
			die("Hubo un error");
		}
	}
	
	
	
	public function load_template(){
		if(file_exists($this->config["pathbase"]."cfw/general/templates/".$this->template.".tpl") || 
			file_exists($this->config["pathbase"]."cfw/specific/templates/".$this->template.".tpl")){
			$tpl = new Template($this->template);
		}else{
			if($this->jhtmlarea){
				$tpl = new Template("elements_html");
				$this->add_include("js",$this->config["urlbase"]."js/jHtmlArea-0.7.5.min.js");
				$this->add_include("css",$this->config["urlbase"]."css/jHtmlArea.css");
			}else{
				$tpl = new Template("elements");
			}
		}	
		return $tpl;
	}
	
	private function load_template_single(){
		if(file_exists($this->config["pathbase"]."cfw/general/templates/".$this->slug.".tpl") || 
			file_exists($this->config["pathbase"]."cfw/specific/templates/".$this->slug.".tpl")){
			$tpl = new Template($this->template_single);
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
	
		
		
	function saveImage($image_data, $s_nombre = "", $anchura = "", $hmax = "")
	{
		$b_ok = false;

		if($anchura == "")
		{
			$anchura = $this->config[$this->config_name.".img.width"];
		}//if

		if($hmax == "")
		{
			$hmax = $this->config[$this->config_name.".img.height"];
		}//if
		
		if($s_nombre != "")
		{
			$b_ext = true;
		}//if
		else
		{
			$s_nombre = $image_data["name"];
			$b_ext = false;
		}//else

		$s_image = $image_data["tmp_name"];

		$a_datos = getimagesize($s_image);

		if($a_datos[2] == 1)
		{
			$img = @imagecreatefromgif($s_image);
			if($b_ext)
			{
				$s_nombre .= ".gif";
			}//if
		}//if
		if($a_datos[2] == 2)
		{
			$img = @imagecreatefromjpeg($s_image);
			if($b_ext)
			{
				$s_nombre .= ".jpg";
			}//if
		}//if
		if($a_datos[2] == 3)
		{
			$img = @imagecreatefrompng($s_image);
			if($b_ext)
			{
				$s_nombre .= ".png";
			}//if
		}//if

		$ratio = ($a_datos[0] / $anchura);
		$altura = ($a_datos[1] / $ratio);
		if($altura > $hmax)
		{
			$anchura2 = $hmax * $anchura / $altura;
			$altura = $hmax;
			$anchura = $anchura2;
		}//if

		$thumb = imagecreatetruecolor($anchura,$altura) or die('Cannot Initialize new GD image stream');
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
			$b_ok = imagegif($thumb,$this->config[$this->config_name.".img.path"].$s_nombre);
		}
		if($a_datos[2] == 2)
		{
			//header("Content-type: image/jpeg");
			$b_ok = imagejpeg($thumb,$this->config[$this->config_name.".img.path"].$s_nombre);
		}//if
		if($a_datos[2] == 3)
		{
			//header("Content-type: image/png");
			$b_ok = imagepng($thumb,$this->config[$this->config_name.".img.path"].$s_nombre);
		}//if

		imagedestroy($thumb);
		
		$this->last_file_name = $s_nombre;

		return $b_ok;
	}//fun
	
		
		
	function saveFile($file_data, $s_nombre = "")
	{	
		if($s_nombre == "")
		{
			$s_nombre = $file_data["name"];
			$b_ext = false;
		}//if
		else
		{
			$b_ext = true;
		}
		
		if($b_ext){
			if($file_data["type"] == "application/octet-stream" || $file_data["type"] == "text/html")
			{
				$s_nombre = $s_nombre.".tpl";
			}elseif($file_data["type"] == "text/css"){
				$s_nombre = $s_nombre.".css";
			}else{
				muere($file_data["type"],1);
			}
		}
		
		$this->last_file_name = $s_nombre;
		return copy($file_data["tmp_name"],$this->config[$this->config_name.".path"].$s_nombre);
	}//fun
	
	
	function guardarCategorias($id,$a_data){
		$cat = new $this->categorias_orm();
		
		$cat->limpiarCategorias($id);
		
		foreach($a_data as $categoria){
			$cat->addCat($id,$categoria);
		}
	}
	
}//class
