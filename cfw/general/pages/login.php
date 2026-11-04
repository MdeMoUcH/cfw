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
		$this->add_include("js",$this->config["urlbase"]."js/jquery-4.0.0.min.js");
		$tpljs = new Template("html/js_var");
		$tpljs->vars["nombre"] = "text_rellena_ambos_campos";
		$tpljs->vars["valor"] = json_js(t("text_rellena_ambos_campos"));
		$this->vars["js"] .= $tpljs->get();
		$this->add_include("js",$this->config["urlbase"]."js/main.js?v=3");
		
		$s_msg = "";
		$b_logueado = false;
		
		$tplmsg = new Template("html/p_color");
		
		if($this->session->isUserLogged()){
			
			$b_logueado = true;
			
			$tplmsg->vars["color"] = "Blue";
			$tplmsg->vars["text"] = t("text_ya_logueado");	
			
			$s_msg = $tplmsg->get();
		}
		
		$s_nick = is_string(@$_POST["nick"]) ? $_POST["nick"] : "";
		$s_pass = is_string(@$_POST["pass"]) ? $_POST["pass"] : "";
		
		if($s_nick != "" && $s_pass != ""){
			$tplmsg->vars["color"] = "Red";
			$tplmsg->vars["text"] = t("text_login_incorrecto");
			
			$a_pass = $this->leerPassCifradas(array("pass"));
			if($a_pass === false){
				//No se ha probado ninguna contraseña: no cuenta como intento.
				$tplmsg->vars["text"] = $this->textoFalloCifrado();
			}else{
				$user = new Usuarios();
				//La clave del contador es el email guardado: MySQL iguala variantes que el texto enviado no.
				$s_clave = $user->getUserByEmail($s_nick) ? $user->resultado[0]["email"] : $s_nick;
				
				if(!intentos_bloqueado($s_clave)){
					if($user->comprobarUserPass($s_nick,$a_pass["pass"])){
						intentos_limpiar($s_clave);
						$this->session->setUserLogged($user->resultado[0]["email"],$user->resultado[0]["id"],$user->resultado[0]["fk_tipo_usuario"],$user->resultado[0]["fk_idioma"]);
						$user->setLastVisit($user->resultado[0]["id"]);
						
						$b_logueado = true;
						$tplmsg->vars["color"] = "Green";
						$tplmsg->vars["text"] = t("text_login_ok");
					}elseif(count($user->errores) >= 1){
						//Un fallo de la BD no cuenta como intento.
						$tplmsg->vars["text"] = t("text_problema_reintentar");
					}else{
						intentos_fallo($s_clave);
					}
				}
				$user->cerrar();
			}
			$s_msg = $tplmsg->get();
		}
		
		$this->vars["menu"] = $this->get_menu("login");
		$tpl = new Template("usuarios");
		$tpl->vars["urlbase"] = $this->config["urlbase"];
		$tpl->vars["titulo"] = t("text_login");
		$this->vars["page_title"] = t("text_login");
		
		
		if(!$b_logueado){
			$s_aviso = $this->prepararCifrado();
			$campos_form = array("nick" => array("title" => t("text_campo_email"),
										"name" => "nick",
										"type" => "text",
										"value" => "",
										"onclick" => ""),
									"pass" => array("title" => t("text_campo_pass"),
										"name" => "pass",
										"type" => "password_cifrado",
										"value" => "",
										"onclick" => "")
								);
			
			$a_buttons = array();
			$a_buttons[] = array("name" => "button",
								"value" => t("text_login"),
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
			
			//Sin clave no se pinta el formulario: no podría enviarse.
			$tpl->vars["content"] = $s_msg.$s_aviso.(Cifrado::clavePublica() !== false ? $form->getForm() : "");
		}else{
			$tpl->vars["content"] = $s_msg;
		}
		
		//Mensaje de una página que cerró la sesión y redirigió aquí (cuenta).
		if($this->s_mensaje_interno == ""){
			$this->s_mensaje_interno = $this->leerFlash();
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
				$tplmsg->vars["text"] = t("text_logout_ok");	
			}else{
				$tplmsg->vars["color"] = "Red";
				$tplmsg->vars["text"] = t("text_no_logueado");	
			}
			
			$this->s_mensaje_interno = $tplmsg->get();
		}
		
		$this->init();
	}
	
}//class
