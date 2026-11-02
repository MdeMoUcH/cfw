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
							$tplaux->vars["text"] = "Los datos se actualizaron correctamente.";
							$tpl->vars["email"] = escapar($_POST["email"]);
						}else{
							$tplaux->vars["text"] = "Hubo algún problema, vuelvaló a intentar más tarde.";
						}
					}elseif(count($ormuser->errores) >= 1){
						//Un fallo de la BD no significa que la contraseña sea incorrecta.
						$tplaux->vars["text"] = "Hubo algún problema, vuelvaló a intentar más tarde.";
					}else{
						$tplaux->vars["text"] = "No se realizó ningún cambio, la contraseña actual no es correcta.";
					}
				}else{
					$tplaux->vars["text"] = "No se realizó ningún cambio, las contraseñas tienen que ser iguales.";
				}
			}else{
				$tplaux->vars["text"] = "No se realizó ningún cambio, el email no era correcto.";
			}
			$tpl->vars["content"] = $tplaux->get();
		}
		
		
		
		$this->show($tpl);
	}
	
}//class
