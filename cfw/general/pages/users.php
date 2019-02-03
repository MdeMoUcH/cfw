<?
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
		if(!$this->session->isUserAdmin()){
			header("Location: ".$this->config["urlbase"]."admin/login");
		}
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
				$tpl->vars["content"] .= "<p><b><a href='".$this->config["urlbase"]."admin/users/id/".$user["id"]."'>".$user["email"]."</a></b>";
				if($this->session->isUserAdmin()){
					$tpl->vars["content"] .= "&nbsp;-&nbsp;<a href='".$this->config["urlbase"]."admin/users/edit/".$user["id"]."'>Editar</a>";
				}
				$tpl->vars["content"] .= "</p>";
			}
		}else{
			$tpl->vars["content"] = $this->title;
			$tpl->vars["content"] = "No hay ningún ".$this->slug.".";
		}
		$orm->cerrar();
		//muere($tpl,1,0);muere($tpl->get(),1);
		$this->show($tpl);
	}
	
	
	function id($id = ""){
		if(!$this->session->isUserAdmin()){
			header("Location: ".$this->config["urlbase"]."admin/login");
		}
		$this->vars["menu"] = $this->get_menu($this->slug);
		
		$tpl = new Template($this->template);
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		$tpl->vars["titulo"] = $this->titulo;
		
		$orm = new $this->class();
		if($id != ""){
			$user = @$orm->getById($id);
			$tpl->vars["titulo"] = $user["email"];
			//$tpl->vars["content"] = "<p>".nl2br($user["descripcion"])."</p>";
			$tpl->vars["content"] = "<p>".$user["email"]."</p>";
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
		if(!$this->session->isUserAdmin()){
			header("Location: ".$this->config["urlbase"]."admin/login");
		}
		
		$b_insert_error = false;
		
		$orm = new $this->class();
		
		$tplmsg = new Template("html/p_color");
		
		if($save != "" && $_POST["pass"] == $_POST["pass_confirm"]){
			if($_POST["pass"] != ""){
				$_POST["pass"] = sha1($_POST["pass"]);
			}
			
			if($_POST["email"] == "" || $_POST["pass"] == ""){
				$b_insert_error = true;
				$tplmsg->vars["color"] = "Red";
				$tplmsg->vars["text"] = "El email y la contraseña no pueden estar vacíos";
				
				$s_msg = $tplmsg->get();
			}elseif($orm->insert($_POST)){
				$tplmsg->vars["color"] = "Green";
				$tplmsg->vars["text"] = "OK - usuario insertado";
				
				$s_msg = $tplmsg->get();
				
				if(@$_FILES["avatar"]["error"] == 0){
					$tplmsg->vars["color"] = "Red";
					$tplmsg->vars["text"] = "WTF? No está hecho el guardado de imágenes.... -_-\"";
					
					//$s_msg .= $tplmsg->get();
				}elseif($_FILES["avatar"]["error"] != 4){
					$tplmsg->vars["color"] = "Red";
					$tplmsg->vars["text"] = "Pero hubo error al subir la imágen";
					
					$s_msg .= $tplmsg->get();
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
		}elseif($save != ""){
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
		if(!$this->session->isUserAdmin()){
			header("Location: ".$this->config["urlbase"]."admin/login");
		}
		$s_msg = "";
		if($save != ""){
			$orm = new $this->class();
			$tplmsg = new Template("html/p_color");
			if($_POST["pass"] != "" && $_POST["pass"] == $_POST["pass_confirm"]){
				$_POST["pass"] = sha1($_POST["pass"]);
			}
			if($orm->update($_POST)){
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
	
	
	
}//class
