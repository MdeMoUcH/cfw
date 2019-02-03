<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/


class Usuarios extends Orm
{
	function __construct()
	{
		parent::__construct();
		
		$this->tabla = "usuarios";
		
		$this->campos = array("id" => "text",
							"fk_idioma" => "text",
							"fk_tipo_usuario" => "num",
							"email" => "text",
							"pass" => "text",
							"fecha" => "date",
							"ultimo_acceso" => "date",
							"visitas" => "num",
							"activo" => "num",
							"borrado" => "num",
							"init" => "num");
							
		$this->primarykey = "id";
	
		//Para los form:
		$this->campos_form = array(
								
								"email" => array(
									"title" => "E-mail",
									"name" => "email",
									"type" => "text"),
								"idioma" => array(
									"title" => "Idioma",
									"name" => "fk_idioma",
									"type" => "radio",
									"value" => "es",
									"opciones" => array("Español" => "es", "Inglés" => "en") ),
								"activo" => array(
									"title" => "Estado",
									"name" => "activo",
									"type" => "radio",
									"value" => "1",
									"opciones" => array("Activo" => "1", "Inactivo" => "0") ),
								"fk_tipo_usuario" => array(
									"title" => "Tipo Usuario",
									"name" => "fk_tipo_usuario",
									"type" => "radio",
									"value" => "0",
									"opciones" => array("Usuario" => "0", "Admin" => "1") )
							);
							
	}//constructor


	function comprobarInvitado($user){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE email = '".$this->filtro($user)."' AND (pass = '' OR pass IS null) AND invitado = 1 AND borrado = 0;";
		
		$this->ejecutarConsulta($s_sql);
		
		if(isset($this->resultado[0])){
			return true;
		}else{
			return false;
		}
	}


	function comprobarUserPass($user, $pass){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE email = '".$this->filtro($user)."' AND pass <> '' AND pass = '".$this->filtro($pass)."' AND borrado = 0;";
		
		return $this->ejecutarConsulta($s_sql);
	}
	
	
	function insert($a_array, $tabla = ''){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE email = '".$this->filtro($a_array["email"])."' AND borrado = 0 AND (pass IS NOT NULL OR pass <> '');";
		
		$this->ejecutarConsulta($s_sql);
		
		if(isset($this->resultado[0]['email'])){
			$tpl = new Template('main');
			$this->errores[] = $tpl->vars['text_error_usuario_mail'];
			return false;
		}else{
			return parent::insert($a_array);
		}
	}
	
	function update($a_datos, $id = ""){
		if(@$a_datos["email"] != ""){
			$s_sql = "SELECT * FROM ".$this->tabla." WHERE email = '".$this->filtro($a_datos["email"])."' AND borrado = 0";
			
			if($this->ejecutarConsulta($s_sql)){
				if($this->resultado[0]["id"] != $id && $this->resultado[0]["id"] != $a_datos["id"]){
					$tpl = new Template('main');
					$this->errores[] = $tpl->vars['text_error_usuario_mail'];
					return false;
				}
			}
		}
		
		return parent::update($a_datos, $id);
	}
	
	
	function setLastVisit($id,$timezone = ''){
		parent::getById($id);
		$a_data = array();
		$a_data["ultimo_acceso"] = date("Y-m-d H:i:s");
		$a_data["visitas"] = $this->resultado[0]['visitas']+1;
		$a_data["timezone"] = $timezone;
		
		return parent::update($a_data,$id);
	}
	
	function getUserNameById($id){
		$s_sql = "SELECT email FROM ".$this->tabla." WHERE id = ".$this->filtro($id)."";
		
		if($this->ejecutarConsulta($s_sql)){
			return $this->resultado[0]["email"];
		}else{
			return "Usuario Desconocido";
		}
	}
	
	
	function getLastUserByFkIdUsuario($id){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE fk_usuario = ".$this->filtro($id)." AND borrado = 0 ORDER by fecha DESC LIMIT 1";
		
		return $this->ejecutarConsulta($s_sql);
	}
	
	function getUsersByFkIdUsuario($id){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE fk_usuario = ".$this->filtro($id)." AND borrado = 0";
		
		return $this->ejecutarConsulta($s_sql);
	}
	
	function getUserByEmail($email){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE email = '".$this->filtro($email)."' AND borrado = 0";
		
		return $this->ejecutarConsulta($s_sql);
	}
}//class
