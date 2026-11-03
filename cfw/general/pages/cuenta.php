<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/





class cuenta extends Page{
	
	function __construct(){
		parent::__construct();
		$this->exigirUsuario();
		$this->vars["menu"] = $this->get_menu("cuenta");
	}
	
	function init($var1="",$var2=""){
		$tpl = new Template("web/cuenta");
		
		$ormuser = new Usuarios();
		
		$ormuser->getById($this->session->data["id_usuario"]);
		$s_email_actual = isset($ormuser->resultado[0]["email"]) ? (string)$ormuser->resultado[0]["email"] : "";
		$tpl->vars["email"] = escapar($s_email_actual);
		
		
		$tpl->vars["content"] = $this->leerFlash();
		
		if(@$_POST["save"] === "editidioma"){
			$tpl->vars["content"] = $this->guardarIdioma($ormuser);
		}
		
		//Sin clave no se puede comprobar la contraseña actual: no se guarda nada y prepararCifrado pinta el aviso.
		$b_cifrado = Cifrado::clavePublica() !== false;
		if(@$_POST["save"] == "edituser" && $b_cifrado){
			$tplaux = new Template("html/p_color");
			$tplaux->vars["color"] = "Red";
			$a_pass = $this->leerPassCifradas(array("pass1","pass2","pass_actual"));
			$s_pass1 = $a_pass ? $a_pass["pass1"] : "";
			$s_pass2 = $a_pass ? $a_pass["pass2"] : "";
			$s_pass_actual = $a_pass ? $a_pass["pass_actual"] : "";
			if($a_pass === false){
				$tplaux->vars["text"] = $this->textoFalloCifrado();
			}elseif(email_valido(@$_POST["email"])){
				if($s_pass1 === $s_pass2){
					//Contador por usuario, sin IP. Bloqueado dentro de la ventana: no se prueba la contraseña.
					$s_intentos = "cuenta|".(int)$this->session->data["id_usuario"];
					if($s_pass_actual != "" && intentos_bloqueado($s_intentos,false)){
						$this->bloquearCuenta();
					}
					//El email guardado ya pasó por filtro(): se decodifica para que no se codifique dos veces.
					if($s_pass_actual != "" && $ormuser->comprobarUserPass(html_entity_decode($s_email_actual,ENT_QUOTES,"UTF-8"),$s_pass_actual)){
						intentos_limpiar($s_intentos,false);
						$a_data = array("email" => $_POST["email"]);
						if($s_pass1 != ""){
							$a_data["pass"] = pass_hash($s_pass1);
						}
						if($s_pass1 != "" && $a_data["pass"] === false){
							$tplaux->vars["text"] = t("text_pass_no_valida");
						}elseif($ormuser->update($a_data,$this->session->data["id_usuario"])){
							$tplaux->vars["color"] = "Green";
							$tplaux->vars["text"] = t("text_cuenta_ok");
							$tpl->vars["email"] = escapar($_POST["email"]);
						}else{
							$tplaux->vars["text"] = t("text_problema_reintentar");
						}
					}elseif(count($ormuser->errores) >= 1){
						//Un fallo de la BD no significa que la contraseña sea incorrecta.
						$tplaux->vars["text"] = t("text_problema_reintentar");
					}else{
						//Una contraseña actual vacía no prueba nada: no cuenta.
						if($s_pass_actual != ""){
							intentos_fallo($s_intentos,false);
							if(intentos_bloqueado($s_intentos,false)){
								$this->bloquearCuenta();
							}
						}
						$tplaux->vars["text"] = t("text_cuenta_pass_incorrecta");
					}
				}else{
					$tplaux->vars["text"] = t("text_cuenta_pass_distintas");
				}
			}else{
				$tplaux->vars["text"] = t("text_cuenta_email_incorrecto");
			}
			$tpl->vars["content"] = $tplaux->get();
		}
		
		$tpl->vars["form_idioma"] = $this->formularioIdioma();
		$tpl->vars["aviso_cifrado"] = $this->prepararCifrado();
		$tpl->vars["form_usuario"] = "";
		if($b_cifrado){
			$tplusuario = new Template("web/cuenta_usuario");
			$tplusuario->vars["email"] = $tpl->vars["email"];
			$tpl->vars["form_usuario"] = $tplusuario->get();
		}
		
		$this->show($tpl);
	}
	
	
	//Solo vuelve si hay que mostrar un error; si guarda, redirige para pintar la página en el idioma nuevo.
	protected function guardarIdioma($ormuser){
		$s_idioma = @$_POST["fk_idioma"];
		$s_clave = "text_cuenta_idioma_invalido";
		
		if(GestorIdiomas::hayVarios() && is_string($s_idioma) && in_array($s_idioma,GestorIdiomas::activos(),true)){
			if($ormuser->update(array("fk_idioma" => $s_idioma),$this->session->data["id_usuario"])){
				$this->session->setLang($s_idioma);
				$this->guardarFlash("Green","text_cuenta_idioma_ok");
				$this->redirigir($this->config["urlbase"]."admin/cuenta");
			}
			$s_clave = "text_problema_reintentar";
		}
		
		$tplaux = new Template("html/p_color");
		$tplaux->vars["color"] = "Red";
		$tplaux->vars["text"] = t($s_clave);
		return $tplaux->get();
	}
	
	
	//No vuelve: cierra la sesión y redirige al login.
	protected function bloquearCuenta(){
		$i_id = (int)$this->session->data["id_usuario"];
		$this->session->setUserOut();
		error_log("CFW: demasiados intentos con la contraseña actual en cuenta; sesión cerrada del usuario ".$i_id);
		$this->guardarFlash("Red","text_cuenta_bloqueada");
		$this->redirigir($this->config["urlbase"]."admin/login");
	}
	
	
	protected function formularioIdioma(){
		if(!GestorIdiomas::hayVarios()){
			return "";
		}
		
		$s_actual = GestorIdiomas::actual();
		$a_disponibles = GestorIdiomas::disponibles();
		$form = new Form();
		$s_opciones = "";
		foreach(GestorIdiomas::activos() as $s_codigo){
			$s_opciones .= $form->getInput("fk_idioma","radio",$s_codigo,"","",$s_codigo === $s_actual,"&nbsp;".escapar($a_disponibles[$s_codigo]))."<br/>";
		}
		
		$tpl = new Template("web/cuenta_idioma");
		$tpl->vars["opciones"] = $s_opciones;
		return $tpl->get();
	}
	
}//class
