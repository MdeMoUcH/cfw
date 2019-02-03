<?
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/




class login extends Page{	
	
	
	function init($s_mensaje = ""){
		$this->add_include("js",$this->config["urlbase"]."js/jquery-1.7.1.min.js");
		$this->add_include("js",$this->config["urlbase"]."js/md5.js");
		$this->add_include("js",$this->config["urlbase"]."js/main.js");
		
		$s_msg = "";
		$b_logueado = false;
		
		$tplmsg = new Template("html/p_color");
		
		if($this->session->isUserLogged()){
			
			$b_logueado = true;
			
			$tplmsg->vars["color"] = "Blue";
			$tplmsg->vars["text"] = "Ya estás logueado";	
			
			$s_msg = $tplmsg->get();
		}
		
		if(@$_POST["nick"] != "" && @$_POST["pass"] != ""){
			
			$user = new Usuarios();
			
			if($user->comprobarUserPass($_POST["nick"],$_POST["pass"])){
				$this->session->setUserLogged($user->resultado[0]["email"],$user->resultado[0]["id"],$user->resultado[0]["fk_tipo_usuario"],$user->resultado[0]["fk_idioma"]);
				$user->setLastVisit($user->resultado[0]["id"]);
				
				$b_logueado = true;
				$tplmsg->vars["color"] = "Green";
				$tplmsg->vars["text"] = "Te has logueado con éxito.";
			}else{
				$tplmsg->vars["color"] = "Red";
				$tplmsg->vars["text"] = "El usuario o la contraseña no son correctos.";	
			}
			$user->cerrar();
			$s_msg = $tplmsg->get();
		}
		
		$this->vars["menu"] = $this->get_menu("main");
		$tpl = new Template("usuarios");
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		$tpl->vars["titulo"] = "Login";
		
		
		if(!$b_logueado){
			$campos_form = array("nick" => array("title" => "E-mail",
										"name" => "nick",
										"type" => "text",
										"value" => "",
										"onclick" => ""),
									"pass" => array("title" => "Contraseña",
										"name" => "pass",
										"type" => "password",
										"value" => "",
										"onclick" => "")
								);
			
			$a_buttons = array();
			$a_buttons[] = array("name" => "button",
								"value" => "Login",
								"class" => "button",
								"onclick" => "javascript:sendLoginForm();");
			$form = new Form();
			
			$form->vars  = array();
			$form->vars["name"] = "formulario";
			$form->setAction($this->config["urlbase"]."login");
			
			$form->setMethod("POST");
			
			if(@$_POST["nick"] != ""){
				$campos_form["nick"]["value"] = $_POST["nick"];
			}
			
			
			$form->addContentTable($campos_form,$a_buttons);
			
			$tpl->vars["content"] = $s_msg.$form->getForm();
		}else{
			$tpl->vars["content"] = $s_msg;
		}
		
		if($s_mensaje != ""){
			$tpl->vars["mensaje"] = $s_mensaje;
		}
		
		$this->show($tpl);
	}
	
	function logout(){
		$tplmsg = new Template("html/p_color");
		
		if($this->session->isUserLogged()){
			$this->session->setUserOut();
			$tplmsg->vars["color"] = "Blue";
			$tplmsg->vars["text"] = "Has salido de tu cuenta.";	
		}else{
			$tplmsg->vars["color"] = "Red";
			$tplmsg->vars["text"] = "No estás logueado.";	
		}
		
		$this->init($tplmsg->get());
	}
	
}//class
