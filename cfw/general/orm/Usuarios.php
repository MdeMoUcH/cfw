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
									"type" => "password_cifrado"),
								"pass_confirm" => array(
									"title" => t("text_campo_pass_confirm"),
									"name" => "pass_confirm",
									"type" => "password_cifrado"),
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


	//Recibe la contraseña real. Los md5 heredados se aceptan y se convierten a password_hash en el primer login correcto.
	function comprobarUserPass($user, $pass){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE email = '".$this->filtro($user)."' AND pass <> '' AND activo = 1 AND borrado = 0;";

		$i_errores = count($this->errores);
		$this->ejecutarConsulta($s_sql);
		$a_filas = $this->resultado;
		$this->resultado = array();

		if(count($a_filas) < 1){
			if(count($this->errores) == $i_errores){
				//Mismo coste que un login real en cualquier versión: no revela qué emails existen.
				password_hash("cfw-ficticia", PASSWORD_DEFAULT);
			}
			return false;
		}
		if(!is_string($pass)){
			return false;
		}

		//Un sitio que aún no haya resuelto sus emails duplicados puede tener varias filas: se prueban todas.
		foreach($a_filas as $a_fila){
			$s_guardado = (string)$a_fila["pass"];
			$b_md5 = preg_match('/^[0-9a-f]{32}$/iD', $s_guardado) === 1;
			$b_ok = $b_md5 ? hash_equals(strtolower($s_guardado), md5($pass)) : password_verify($pass, $s_guardado);
			if(!$b_ok){
				if($b_md5){
					//Mismo coste que bcrypt: que no se distingan por tiempo las cuentas aún en md5.
					password_hash("cfw-ficticia", PASSWORD_DEFAULT);
				}
				continue;
			}

			//Un fallo al rehashear no impide entrar ni deja errores: quien llama los toma como fallo de la BD.
			if(($b_md5 || password_needs_rehash($s_guardado, PASSWORD_DEFAULT)) && pass_valida($pass)){
				$i_antes = count($this->errores);
				if(!$this->updateField($a_fila["id"], "pass", pass_hash($pass))){
					error_log("CFW: no se pudo actualizar el hash de la contraseña del usuario ".$a_fila["id"]);
					array_splice($this->errores, $i_antes);
				}
			}

			$this->resultado = array($a_fila);
			return true;
		}

		return false;
	}
	
	
	function insert($a_array, $tabla = ''){
		//Solo los no borrados, como el UNIQUE de email_activo: el email de un borrado se puede reutilizar.
		$s_sql = "SELECT id FROM ".$this->tabla." WHERE email = '".$this->filtro(@$a_array["email"])."' AND borrado = 0";
		
		$i_errores = count($this->errores);
		$this->ejecutarConsulta($s_sql);
		//Sin poder comprobar el email no se escribe: un sitio sin el UNIQUE no tendría otra protección.
		if(count($this->errores) > $i_errores){
			return false;
		}
		
		if(isset($this->resultado[0]['id'])){
			$this->errores[] = t('text_error_usuario_mail');
			return false;
		}else{
			$a_array = $this->normalizarIdioma($a_array);
			if(!isset($a_array["fk_idioma"])){
				$a_array["fk_idioma"] = GestorIdiomas::porDefecto();
			}
			return $this->emailRepetido(parent::insert($a_array));
		}
	}
	
	function update($a_datos, $id = ""){
		$id_efectivo = ($id != "") ? $id : @$a_datos["id"];
		if(@$a_datos["email"] != ""){
			$s_sql = "SELECT id FROM ".$this->tabla." WHERE email = '".$this->filtro($a_datos["email"])."' AND borrado = 0";
			
			$i_errores = count($this->errores);
			if($this->ejecutarConsulta($s_sql)){
				//Cualquier otra fila no borrada con ese email lo hace repetido.
				foreach($this->resultado as $a_fila){
					if($a_fila["id"] != $id_efectivo){
						$this->errores[] = t('text_error_usuario_mail');
						return false;
					}
				}
			}elseif(count($this->errores) > $i_errores){
				return false;
			}
		}
		
		return $this->emailRepetido(parent::update($this->normalizarIdioma($a_datos), $id));
	}
	
	
	//Si el UNIQUE de email_activo (o el de email, sin migrar) rechaza la escritura (otra petición se adelantó), se avisa igual que en la comprobación previa.
	protected function emailRepetido($b_ok){
		if(!$b_ok && $this->conexion && mysqli_errno($this->conexion) === 1062
			&& preg_match("/ for key '(?:[^']*\\.)?email(?:_activo)?'$/", mysqli_error($this->conexion)) === 1
			&& end($this->errores) === t("text_bd_escritura")){
			array_pop($this->errores);
			$this->errores[] = t('text_error_usuario_mail');
		}
		return $b_ok;
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
	
	
	function setLastVisit($id){
		if(($id = $this->filtroId($id)) === false){
			return false;
		}
		if(!$this->filaEditable("id", $id)){
			return false;
		}
		//El incremento va en el propio UPDATE para no perder visitas simultáneas.
		$s_sql = "UPDATE ".$this->tabla." SET ultimo_acceso = '".date("Y-m-d H:i:s")."', visitas = visitas + 1 WHERE id = ".$id." AND borrado = 0";
		
		return $this->ejecutarInsertUpdate($s_sql);
	}
	
	function getUserNameById($id){
		if(($id = $this->filtroId($id)) === false){
			return t("text_usuario_desconocido");
		}
		$s_sql = "SELECT email FROM ".$this->tabla." WHERE id = ".$id." AND borrado = 0";
		
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
