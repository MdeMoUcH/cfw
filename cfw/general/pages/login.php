<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/




class login extends Page{	
	
	//No se recibe por parámetro: init es accesible por URL.
	protected $s_mensaje_interno = "";
	
	
	function init(){
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
		
		$s_nick = is_string(@$_POST["nick"]) ? $_POST["nick"] : "";
		$s_pass = is_string(@$_POST["pass"]) ? $_POST["pass"] : "";
		
		if($s_nick != "" && $s_pass != ""){
			$tplmsg->vars["color"] = "Red";
			$tplmsg->vars["text"] = "El usuario o la contraseña no son correctos.";
			
			$user = new Usuarios();
			//La clave del contador es el email guardado: MySQL iguala variantes que el texto enviado no.
			$s_clave = $user->getUserByEmail($s_nick) ? $user->resultado[0]["email"] : $s_nick;
			
			if(!intentos_bloqueado($s_clave)){
				if($user->comprobarUserPass($s_nick,$s_pass)){
					intentos_limpiar($s_clave);
					$this->session->setUserLogged($user->resultado[0]["email"],$user->resultado[0]["id"],$user->resultado[0]["fk_tipo_usuario"],$user->resultado[0]["fk_idioma"]);
					$user->setLastVisit($user->resultado[0]["id"]);
					
					$b_logueado = true;
					$tplmsg->vars["color"] = "Green";
					$tplmsg->vars["text"] = "Te has logueado con éxito.";
				}elseif(count($user->errores) >= 1){
					//Un fallo de la BD no cuenta como intento.
					$tplmsg->vars["text"] = "Hubo algún problema, vuélvalo a intentar más tarde.";
				}else{
					intentos_fallo($s_clave);
				}
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
			
			if($s_nick != ""){
				$campos_form["nick"]["value"] = $s_nick;
			}
			
			
			$form->addContentTable($campos_form,$a_buttons);
			
			$tpl->vars["content"] = $s_msg.$form->getForm();
		}else{
			$tpl->vars["content"] = $s_msg;
		}
		
		if($this->s_mensaje_interno != ""){
			$tpl->vars["mensaje"] = $this->s_mensaje_interno;
		}
		
		$this->show($tpl);
	}
	
	function logout(){
		if(@$_SERVER["REQUEST_METHOD"] === "POST"){
			$tplmsg = new Template("html/p_color");
			
			if($this->session->isUserLogged()){
				$this->session->setUserOut();
				$tplmsg->vars["color"] = "Blue";
				$tplmsg->vars["text"] = "Has salido de tu cuenta.";	
			}else{
				$tplmsg->vars["color"] = "Red";
				$tplmsg->vars["text"] = "No estás logueado.";	
			}
			
			$this->s_mensaje_interno = $tplmsg->get();
		}
		
		$this->init();
	}
	
}//class
