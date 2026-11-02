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
		
		if(@$_POST["save"] == "edituser"){
			$tplaux = new Template("html/p_color");
			$tplaux->vars["color"] = "Red";
			$s_pass1 = is_string(@$_POST["pass1"]) ? $_POST["pass1"] : "";
			$s_pass2 = is_string(@$_POST["pass2"]) ? $_POST["pass2"] : "";
			$s_pass_actual = is_string(@$_POST["pass_actual"]) ? $_POST["pass_actual"] : "";
			if(email_valido(@$_POST["email"])){
				if($s_pass1 === $s_pass2){
					//El email guardado ya pasó por filtro(): se decodifica para que no se codifique dos veces.
					if($s_pass_actual != "" && $ormuser->comprobarUserPass(html_entity_decode($s_email_actual,ENT_QUOTES,"UTF-8"),md5($s_pass_actual))){
						$a_data = array("email" => $_POST["email"]);
						if($s_pass1 != ""){
							$a_data["pass"] = md5($s_pass1);
						}
						if($ormuser->update($a_data,$this->session->data["id_usuario"])){
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
