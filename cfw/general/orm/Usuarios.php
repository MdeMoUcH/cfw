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
							"borrado" => "num");
							
		$this->primarykey = "id";
	
		//Para los form:
		$this->campos_form = array(
								
								"email" => array(
									"title" => t("text_campo_email"),
									"name" => "email",
									"type" => "text"),
								"pass" => array(
									"title" => t("text_campo_pass"),
									"name" => "pass",
									"type" => "password"),
								"pass_confirm" => array(
									"title" => t("text_campo_pass_confirm"),
									"name" => "pass_confirm",
									"type" => "password"),
								"idioma" => array(
									"title" => t("text_campo_idioma"),
									"name" => "fk_idioma",
									"type" => "radio",
									"value" => GestorIdiomas::porDefecto(),
									"opciones" => array() ),
								"activo" => array(
									"title" => t("text_campo_estado"),
									"name" => "activo",
									"type" => "radio",
									"value" => "1",
									"opciones" => array(t("text_activo") => "1", t("text_inactivo") => "0") ),
								"fk_tipo_usuario" => array(
									"title" => t("text_campo_tipo"),
									"name" => "fk_tipo_usuario",
									"type" => "radio",
									"value" => "0",
									"opciones" => array(t("text_tipo_usuario") => "0", t("text_tipo_admin") => "1") )
							);
		
		if(GestorIdiomas::hayVarios()){
			$a_disponibles = GestorIdiomas::disponibles();
			foreach(GestorIdiomas::activos() as $s_codigo){
				$this->campos_form["idioma"]["opciones"][$a_disponibles[$s_codigo]] = $s_codigo;
			}
		}else{
			unset($this->campos_form["idioma"]);
		}
							
	}//constructor


	function comprobarUserPass($user, $pass){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE email = '".$this->filtro($user)."' AND pass <> '' AND pass = '".$this->filtro($pass)."' AND activo = 1 AND borrado = 0;";
		
		return $this->ejecutarConsulta($s_sql);
	}
	
	
	function insert($a_array, $tabla = ''){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE email = '".$this->filtro(@$a_array["email"])."' AND borrado = 0 AND (pass IS NOT NULL AND pass <> '');";
		
		$i_errores = count($this->errores);
		$this->ejecutarConsulta($s_sql);
		//Sin poder comprobar el email no se escribe: la tabla no tiene UNIQUE.
		if(count($this->errores) > $i_errores){
			return false;
		}
		
		if(isset($this->resultado[0]['email'])){
			$this->errores[] = t('text_error_usuario_mail');
			return false;
		}else{
			$a_array = $this->normalizarIdioma($a_array);
			if(!isset($a_array["fk_idioma"])){
				$a_array["fk_idioma"] = GestorIdiomas::porDefecto();
			}
			return parent::insert($a_array);
		}
	}
	
	function update($a_datos, $id = ""){
		$id_efectivo = ($id != "") ? $id : @$a_datos["id"];
		if(@$a_datos["email"] != ""){
			$s_sql = "SELECT * FROM ".$this->tabla." WHERE email = '".$this->filtro($a_datos["email"])."' AND borrado = 0";
			
			$i_errores = count($this->errores);
			if($this->ejecutarConsulta($s_sql)){
				if($this->resultado[0]["id"] != $id_efectivo){
					$this->errores[] = t('text_error_usuario_mail');
					return false;
				}
			}elseif(count($this->errores) > $i_errores){
				return false;
			}
		}
		
		return parent::update($this->normalizarIdioma($a_datos), $id);
	}
	
	
	//Un idioma no activo no se guarda: acaba decidiendo qué ficheros se leen.
	protected function normalizarIdioma($a_datos){
		if(array_key_exists("fk_idioma",$a_datos)){
			$valor = is_array($a_datos["fk_idioma"]) ? @$a_datos["fk_idioma"][0] : $a_datos["fk_idioma"];
			if(is_string($valor) && in_array($valor,GestorIdiomas::activos(),true)){
				$a_datos["fk_idioma"] = $valor;
			}else{
				unset($a_datos["fk_idioma"]);
			}
		}
		return $a_datos;
	}
	
	
	function setLastVisit($id,$timezone = ''){
		$a_fila = parent::getById($id);
		$a_data = array();
		$a_data["ultimo_acceso"] = date("Y-m-d H:i:s");
		//Sin la fila no se conoce el contador: no se pisa.
		if($a_fila){
			$a_data["visitas"] = $a_fila["visitas"]+1;
		}
		$a_data["timezone"] = $timezone;
		
		return parent::update($a_data,$id);
	}
	
	function getUserNameById($id){
		if(($id = $this->filtroId($id)) === false){
			return t("text_usuario_desconocido");
		}
		$s_sql = "SELECT email FROM ".$this->tabla." WHERE id = ".$id."";
		
		if($this->ejecutarConsulta($s_sql)){
			return $this->resultado[0]["email"];
		}else{
			return t("text_usuario_desconocido");
		}
	}
	
	
	function getUserByEmail($email){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE email = '".$this->filtro($email)."' AND borrado = 0";
		
		return $this->ejecutarConsulta($s_sql);
	}
}//class
