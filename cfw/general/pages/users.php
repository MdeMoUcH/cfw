<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/


class users extends Page{
	
	protected $class = "Usuarios";
	protected $titulo = "";
	protected $slug = "usuarios";
	protected $template = "usuarios_admin";
	
	
	function __construct(){
		parent::__construct();
		$this->titulo = t("text_usuarios");
	}
	
	
	function init(){
		$this->exigirAdmin();
		$this->vars["menu"] = $this->get_menu($this->slug);
		
		$tpl = new Template($this->template);
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		$tpl->vars["titulo"] = $this->titulo;
		
		$orm = new $this->class();
		
		$users = $orm->getAll();
		if($users){
			$tpl->vars["content"] = "";
			foreach($users as $user){
				//meter en template ¿o qué? majo...
				$tpl->vars["content"] .= "<p><b><a href='".$this->config["urlbase"]."admin/users/id/".$user["id"]."'>".escapar($user["email"])."</a></b>";
				if($this->session->isUserAdmin()){
					$tpl->vars["content"] .= "&nbsp;-&nbsp;<a href='".$this->config["urlbase"]."admin/users/edit/".$user["id"]."'>".t("text_editar")."</a>";
				}
				$tpl->vars["content"] .= "</p>";
			}
		}else{
			$tpl->vars["content"] = t("text_no_hay_usuarios");
		}
		$orm->cerrar();
		//muere($tpl,1,0);muere($tpl->get(),1);
		$this->show($tpl);
	}
	
	
	function id($id = ""){
		$this->exigirAdmin();
		$this->vars["menu"] = $this->get_menu($this->slug);
		
		$tpl = new Template($this->template);
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		$tpl->vars["titulo"] = $this->titulo;
		
		$orm = new $this->class();
		$user = ($id != "") ? $orm->getById($id) : false;
		if($user){
			$tpl->vars["titulo"] = escapar($user["email"]);
			//$tpl->vars["content"] = "<p>".nl2br($user["descripcion"])."</p>";
			$tpl->vars["content"] = "<p>".escapar($user["email"])."</p>";
			if($this->session->isUserAdmin()){
				$tpl->vars["content"] .= "<p><a href='".$this->config["urlbase"]."admin/users/edit/".$user["id"]."'>".t("text_editar")."</a></p>";
			}			
		}else{
			$tpl->vars["content"] = $this->titulo;
			$tpl->vars["content"] = t("text_usuario_no_encontrado");
		}
		$orm->cerrar();
		
		$this->show($tpl);
	}
	
	
		
	function form($save = "", $id = "",$s_msg = ""){
		$this->exigirAdmin();
		
		$b_insert_error = false;
		
		$orm = new $this->class();
		
		$tplmsg = new Template("html/p_color");
		
		$a_datos = $this->datosFormulario();
		//Al ORM solo llega el hash, nunca el texto cifrado.
		unset($a_datos["pass"]);
		$b_guardar = $save != "" && @$_SERVER["REQUEST_METHOD"] === "POST";
		$b_cifrado = Cifrado::clavePublica() !== false;
		$a_pass = $b_guardar ? $this->leerPassCifradas(array("pass","pass_confirm")) : false;
		$s_pass = $a_pass ? $a_pass["pass"] : "";
		$s_pass_confirm = $a_pass ? $a_pass["pass_confirm"] : "";
		
		if($b_guardar && $a_pass === false){
			$b_insert_error = true;
			$tplmsg->vars["color"] = "Red";
			$tplmsg->vars["text"] = $this->textoFalloCifrado();
			$s_msg = $tplmsg->get();
		}elseif($b_guardar && !$b_cifrado){
			//El alta exige contraseña y sin clave no puede llegar: el aviso de prepararCifrado lo explica.
			$b_insert_error = true;
		}elseif($b_guardar && $s_pass === $s_pass_confirm){
			if($s_pass != ""){
				$a_datos["pass"] = pass_hash($s_pass);
			}
			
			if($s_pass == "" || !email_valido(@$a_datos["email"])){
				$b_insert_error = true;
				$tplmsg->vars["color"] = "Red";
				$tplmsg->vars["text"] = t("text_users_email_pass");
				
				$s_msg = $tplmsg->get();
			}elseif($a_datos["pass"] === false){
				$b_insert_error = true;
				$tplmsg->vars["color"] = "Red";
				$tplmsg->vars["text"] = t("text_pass_no_valida");
				
				$s_msg = $tplmsg->get();
			}elseif($this->hayArchivoEnviado()){
				$b_insert_error = true;
				$tplmsg->vars["color"] = "Red";
				$tplmsg->vars["text"] = t("text_users_sin_archivos");
				
				$s_msg = $tplmsg->get();
			}elseif($orm->insert($a_datos)){
				$tplmsg->vars["color"] = "Green";
				$tplmsg->vars["text"] = t("text_users_insertado");
				
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
		}elseif($b_guardar){
			$b_insert_error = true;
			$tplmsg->vars["color"] = "Red";
			$tplmsg->vars["text"] = t("text_pass_distintas");
			$s_msg = $tplmsg->get();
		}
		
		$s_action = "form/save";
		$a_values = array();
		$b_edicion = false;
		if($id != ""){
			if($orm->getByIdAdmin($id)){
				$b_edicion = true;
				$a_values = $orm->resultado[0];
				$orm->campos_form["id"] = array("title" => "","name" => "id","type" => "hidden");
				$orm->campos_form["borrado"] = array("title" => t("text_campo_borrado"),"name" => "borrado","type" => "radio", "opciones" => array(t("text_si") => "1", t("text_no") => "0"));
				$s_action = "edit/".$id."/save";
			}
		}elseif(count($_POST) >= 1 && $b_insert_error){
			$a_values = $_POST;
		}
		
		$orm->cerrar();
		
		//Sin clave: el alta no se pinta y la edición, sin contraseña, para que no se escriba una que no se guardaría.
		if(!$b_cifrado){
			unset($orm->campos_form["pass"], $orm->campos_form["pass_confirm"]);
		}
		
		$form = new Form();
		
		$form->vars  = array();
		$form->vars["name"] = "formulario";
		$form->setAction($this->config["urlbase"]."admin/users/".$s_action);
		$form->setMethod("POST");
		
		$form->addContentTable($orm->campos_form,"",$a_values);
		
		$this->vars["menu"] = $this->get_menu($this->slug);
		
		$tpl = new Template($this->slug);
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		$tpl->vars["titulo"] = t("text_titulo_form",array("titulo" => $this->titulo));
		
		$tpl->vars["content"] = $s_msg.$this->prepararCifrado().(($b_cifrado || $b_edicion) ? $form->getForm() : "");
			
		$this->show($tpl);
	}
	
	
	
	function edit($id, $save = ""){
		$this->exigirAdmin();
		$s_msg = "";
		if($save != "" && @$_SERVER["REQUEST_METHOD"] === "POST"){
			$orm = new $this->class();
			$tplmsg = new Template("html/p_color");
			$a_datos = $this->datosFormulario();
			//Al ORM solo llega el hash, nunca el texto cifrado.
			unset($a_datos["pass"]);
			$a_pass = $this->leerPassCifradas(array("pass","pass_confirm"));
			$s_pass = $a_pass ? $a_pass["pass"] : "";
			$s_pass_confirm = $a_pass ? $a_pass["pass_confirm"] : "";
			if($s_pass != ""){
				$a_datos["pass"] = pass_hash($s_pass);
			}
			
			$tplmsg->vars["color"] = "Red";
			if($a_pass === false){
				$tplmsg->vars["text"] = $this->textoFalloCifrado();
				$s_msg = $tplmsg->get();
			}elseif($s_pass !== $s_pass_confirm){
				$tplmsg->vars["text"] = t("text_pass_distintas");
				$s_msg = $tplmsg->get();
			}elseif($s_pass != "" && $a_datos["pass"] === false){
				$tplmsg->vars["text"] = t("text_pass_no_valida");
				$s_msg = $tplmsg->get();
			}elseif(!email_valido(@$a_datos["email"])){
				$tplmsg->vars["text"] = t("text_email_incorrecto");
				$s_msg = $tplmsg->get();
			}elseif($this->seQuedariaSinAcceso($id,$a_datos)){
				$tplmsg->vars["text"] = t("text_autobloqueo");
				$s_msg = $tplmsg->get();
			}elseif($orm->update($a_datos,$id)){
				$tplmsg->vars["color"] = "Green";
				$tplmsg->vars["text"] = t("text_usuario_actualizado");
					
				$s_msg = $tplmsg->get();
				
				//guardar imágen
			}else{
				
				$tplmsg->vars["text"] = "";
				
				foreach($orm->errores as $error){
					$tplmsg->vars["color"] = "Red";
					$tplmsg->vars["text"] = $error;
					
					$s_msg .= $tplmsg->get();
				}
			}
		}
		$this->form("",$id,$s_msg);
	}
	
	
	//Solo los campos que el formulario puede cambiar; los radios llegan como array.
	protected function datosFormulario(){
		$a_datos = array();
		foreach(array("email","pass","fk_idioma","activo","fk_tipo_usuario","borrado") as $s_campo){
			$valor = @$_POST[$s_campo];
			if(is_array($valor)){
				$valor = @$valor[0];
			}
			if(is_string($valor)){
				$a_datos[$s_campo] = $valor;
			}
		}
		return $a_datos;
	}
	
	
	//Usuarios no tiene dónde guardar un archivo; avatar[] llega con error como array.
	protected function hayArchivoEnviado(){
		foreach($_FILES as $a_file){
			if($a_file["error"] !== UPLOAD_ERR_NO_FILE){
				return true;
			}
		}
		return false;
	}
	
	
	protected function seQuedariaSinAcceso($id,$a_datos){
		if((int)$id !== (int)$this->session->data["id_usuario"]){
			return false;
		}
		return (isset($a_datos["activo"]) && $a_datos["activo"] !== "1")
			|| (isset($a_datos["borrado"]) && $a_datos["borrado"] !== "0")
			|| (isset($a_datos["fk_tipo_usuario"]) && $a_datos["fk_tipo_usuario"] !== "1");
	}
	
	
	
}//class
