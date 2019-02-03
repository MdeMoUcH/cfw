<?
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
		if(!$this->session->isUserLogged()){
			header("Location: ".$this->config["urlbase"]);
		}
		$this->vars["menu"] = $this->get_menu("cuenta");
	}
	
	function init($var1="",$var2=""){
		$tpl = new Template("web/cuenta");
		
		$ormuser = new Usuarios();
		
		$ormuser->getById($this->session->data["id_usuario"]);
		$tpl->vars["email"] = $ormuser->resultado[0]["email"];
		$tpl->vars["nick"] = $ormuser->resultado[0]["nick"];
		
		
		if(@$_POST["save"] == "edituser"){
			$tplaux = new Template("html/p_color");
			$tplaux->vars["color"] = "Red";
			$re = '#^[a-z0-9.!\#$%&\'*+-/=?^_`{|}~]+@([0-9.]+|([^\s]+\.+[a-z]{2,6}))(\.[a-z]{2,6})?$#si';
			if(preg_match($re, $_POST["email"])){
				if($_POST["pass1"] == $_POST["pass2"]){
					$a_data = array("id" => $this->session->data["id_usuario"],
									"email" => $_POST["email"],
									"nick" => $_POST["nick"]);
					if($_POST["pass1"] != ""){
						$a_data["pass"] = sha1($_POST["pass1"]);
					}
					if($ormuser->update($a_data)){
						$tplaux->vars["color"] = "Green";
						$tplaux->vars["text"] = "Los datos se actualizaron correctamente.";
						$tpl->vars["nick"] = $_POST["nick"];
						$tpl->vars["email"] = $_POST["email"];
					}else{
						$tplaux->vars["text"] = "Hubo algún problema, vuelvaló a intentar más tarde.";
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
