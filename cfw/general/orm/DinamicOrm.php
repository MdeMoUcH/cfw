<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/




class DinamicOrm extends Orm
{
    function __construct()
    {
		parent::__construct();
		
		$this->tabla = "";
		
		$this->campos = array("id" => "num",
							"name" => "text");
							
		$this->primarykey = "id";
	
		//Para los form:
		$this->campos_form = array(
								"name" => array("title" => "Título",
									"name" => "titulo",
									"type" => "text")
							);
							
    }//constructor
    
    function setTabla($tabla)
    {
		$this->tabla = $tabla;
	}
	
	function insert($a_array, $tabla = ""){
		if(!isset($a_array["id"]) || $a_array["id"] === ""){
			return parent::insert($a_array);
		}
		if(($id = $this->filtroId($a_array["id"])) === false){
			return false;
		}
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE id = '".$id."'";
		
		if($this->ejecutarConsulta($s_sql)){
			$this->errores[] = "Hubo algún error.";
			return false;
		}else{
			return parent::insert($a_array);
		}
	}
}//class
