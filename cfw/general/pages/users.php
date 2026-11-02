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
	protected $titulo = "Usuarios";
	protected $slug = "usuarios";
	protected $template = "usuarios_admin";
	
	
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
					$tpl->vars["content"] .= "&nbsp;-&nbsp;<a href='".$this->config["urlbase"]."admin/users/edit/".$user["id"]."'>Editar</a>";
				}
				$tpl->vars["content"] .= "</p>";
			}
		}else{
			$tpl->vars["content"] = "No hay ningún ".$this->slug.".";
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
				$tpl->vars["content"] .= "<p><a href='".$this->config["urlbase"]."admin/users/edit/".$user["id"]."'>Editar</a></p>";
			}			
		}else{
			$tpl->vars["content"] = $this->titulo;
			$tpl->vars["content"] = "No se encontró el ".$this->slug.".";
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
		$s_pass = isset($a_datos["pass"]) ? $a_datos["pass"] : "";
		$s_pass_confirm = is_string(@$_POST["pass_confirm"]) ? $_POST["pass_confirm"] : "";
		$b_guardar = $save != "" && @$_SERVER["REQUEST_METHOD"] === "POST";
		
		if($b_guardar && $s_pass === $s_pass_confirm){
			if($s_pass != ""){
				$a_datos["pass"] = md5($s_pass);
			}
			
			if($s_pass == "" || !email_valido(@$a_datos["email"])){
				$b_insert_error = true;
				$tplmsg->vars["color"] = "Red";
				$tplmsg->vars["text"] = "El email tiene que ser correcto y la contraseña no puede estar vacía";
				
				$s_msg = $tplmsg->get();
			}elseif($this->hayArchivoEnviado()){
				$b_insert_error = true;
				$tplmsg->vars["color"] = "Red";
				$tplmsg->vars["text"] = "No se ha creado el usuario: este formulario no admite archivos.";
				
				$s_msg = $tplmsg->get();
			}elseif($orm->insert($a_datos)){
				$tplmsg->vars["color"] = "Green";
				$tplmsg->vars["text"] = "OK - usuario insertado";
				
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
			$tplmsg->vars["text"] = "Las contraseñas no son iguales";
			$s_msg = $tplmsg->get();
		}
		
		$s_action = "form/save";
		$a_values = array();
		if($id != ""){
			if($orm->getByIdAdmin($id)){
				$a_values = $orm->resultado[0];
				$orm->campos_form["id"] = array("title" => "","name" => "id","type" => "hidden");
				$orm->campos_form["borrado"] = array("title" => "Borrado","name" => "borrado","type" => "radio", "opciones" => array("Si" => "1", "No" => "0"));
				$s_action = "edit/".$id."/save";
			}
		}elseif(count($_POST) >= 1 && $b_insert_error){
			$a_values = $_POST;
		}
		
		$orm->cerrar();
		
		$form = new Form();
		
		$form->vars  = array();
		$form->vars["name"] = "formulario";
		$form->setAction($this->config["urlbase"]."admin/users/".$s_action);
		$form->setMethod("POST");
		
		$form->addContentTable($orm->campos_form,"",$a_values);
		
		$this->vars["menu"] = $this->get_menu($this->slug);
		
		$tpl = new Template($this->slug);
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		$tpl->vars["titulo"] = $this->titulo." Form";
		
		$tpl->vars["content"] = $s_msg.$form->getForm();
			
		$this->show($tpl);
	}
	
	
	
	function edit($id, $save = ""){
		$this->exigirAdmin();
		$s_msg = "";
		if($save != "" && @$_SERVER["REQUEST_METHOD"] === "POST"){
			$orm = new $this->class();
			$tplmsg = new Template("html/p_color");
			$a_datos = $this->datosFormulario();
			$s_pass = isset($a_datos["pass"]) ? $a_datos["pass"] : "";
			$s_pass_confirm = is_string(@$_POST["pass_confirm"]) ? $_POST["pass_confirm"] : "";
			unset($a_datos["pass"]);
			if($s_pass != ""){
				$a_datos["pass"] = md5($s_pass);
			}
			
			$tplmsg->vars["color"] = "Red";
			if($s_pass !== $s_pass_confirm){
				$tplmsg->vars["text"] = "Las contraseñas no son iguales";
				$s_msg = $tplmsg->get();
			}elseif(!email_valido(@$a_datos["email"])){
				$tplmsg->vars["text"] = "El email no es correcto";
				$s_msg = $tplmsg->get();
			}elseif($this->seQuedariaSinAcceso($id,$a_datos)){
				$tplmsg->vars["text"] = "No puedes desactivarte, borrarte ni quitarte el rol de administrador a ti mismo";
				$s_msg = $tplmsg->get();
			}elseif($orm->update($a_datos,$id)){
				$tplmsg->vars["color"] = "Green";
				$tplmsg->vars["text"] = "Usuario actualizado con éxito.";
					
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
