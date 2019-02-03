<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/




class Orm extends Bbdd
{
    function __construct()
    {
		parent::__construct();
		
        $this->tabla = "";
        $this->campos = array();
    }//constructor
    
    function filtro($cadena = "")
	{
		//return htmlentities(strip_tags(trim($cadena)), ENT_COMPAT, "UTF-8");
		return mysqli_real_escape_string($this->conexion,htmlentities(strip_tags(trim($cadena),''), ENT_COMPAT, "UTF-8"));
		//return mysqli_real_escape_string($this->conexion,trim($cadena));
	}//fun filtro_mysql
	
	function select($s_sql,$b_solouno = false)
	{
		
		if($this->ejecutarConsulta($s_sql))
		{
			if($b_solouno){
				return $this->resultado[0];
			}else{
				return $this->resultado;
			}
		}else{
			return false;
		}
	}
	
	
    public function delete($id){
		return $this->ejecutarInsertUpdate("UPDATE ".$this->tabla." SET borrado = 1 WHERE id = ".$id."");
	}
	
    
    function getByIdAdmin($id)
    {
		return $this->select("SELECT * FROM ".$this->tabla." WHERE id = ".$id." AND borrado = 0",1);
	}
	
	function getById($id)
    {
		return $this->select("SELECT * FROM ".$this->tabla." WHERE id = ".$id." AND borrado = 0",1);
	}
	
	function getActivoById($id)
    {
		return $this->select("SELECT * FROM ".$this->tabla." WHERE id = ".$id." AND borrado = 0 AND activo = 1",1);
	}
	
	function getByFkId($s_campo, $id)
    {
		return $this->select("SELECT * FROM ".$this->tabla." WHERE ".$s_campo." = ".$id." AND activo = 1 AND borrado = 0");
	}
	
	
	function getBySlug($slug)
    {
		return $this->select("SELECT * FROM ".$this->tabla." WHERE slug = '".$slug."' AND activo = 1 AND borrado = 0",1);
	}
	
	
	function getAll(){
		//die("SELECT * FROM ".$this->tabla."  WHERE borrado = 0 LIMIT ".$this->config["db.limit"]."");
		return $this->select("SELECT * FROM ".$this->tabla."  WHERE borrado = 0 LIMIT ".$this->config["db.limit"]."");
	}
	
	function getAllActivo(){
		return $this->select("SELECT * FROM ".$this->tabla."  WHERE borrado = 0 AND activo = 1 LIMIT ".$this->config["db.limit"]."");
	}
	
	function getAllWhere($s_where){
		return $this->select("SELECT * FROM ".$this->tabla." WHERE ".$s_where." AND borrado = 0");
	}
	
	function getAllPaginado($pag,$elementos){
		$limit_1 = ($pag-1) * $elementos;
		$limit_2 = $elementos;
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE borrado = 0 LIMIT ".$limit_1.",".$limit_2."";
		//die($s_sql);
		return $this->select($s_sql);
	}
	
	function getTotal(){
		if($this->select("SELECT count(*) as total FROM ".$this->tabla." WHERE borrado = 0")){
			return $this->resultado[0]["total"];
		}else{
			return 0;
		}
	}
	
	
	function update($a_datos, $id = ""){
		if($id == "" && $a_datos["id"] !=  ""){
			$id = $a_datos["id"];
		}elseif($id == "" && $a_datos["id"] ==  ""){
			$this->errores[] = "No se especificó un id.";
			return false;
		}
		$a_campos = array();
		foreach($this->campos as $name=>$type){
			$a_campos[] = $name;
		}
		$s_query = "";
		foreach($a_datos as $name=>$value){
			if($value != "" && in_array($name,$a_campos)){
				if(is_array($value)){
						$value = $this->filtro($value[0]);
				}
				$value = "'".$this->filtro($value)."'";
				if($s_query != ""){
					$s_query .= ", ";
				}
				$s_query .= $name." = ".$value;
			}
		}
		$s_sql = "UPDATE ".$this->tabla." SET ".$s_query." WHERE id = ".$id."";
		
		return $this->ejecutarInsertUpdate($s_sql);
	}
	
	
	function insert($a_datos, $s_tabla = ""){
		$a_campos = array();
		foreach($this->campos as $name=>$type){
			$a_campos[] = $name;
		}
		$s_campos = "";
		$s_values = "";
		foreach($a_datos as $name=>$value){
			if($value != "" && in_array($name,$a_campos)){
				if(is_array($value)){
					$value = $this->filtro($value[0]);
				}
				$value = "'".$this->filtro($value)."'";
				if($s_campos != ""){
					$s_campos .= ", ";
					$s_values .= ", ";
				}
				$s_campos .= $name;
				$s_values .= $value;
			}
		}
		
		if($s_tabla == ""){
			$s_tabla = $this->tabla;
		}
		
		$s_sql = "INSERT INTO ".$s_tabla." (".$s_campos.") VALUES (".$s_values.")";
		
		return $this->ejecutarInsertUpdate($s_sql);
	}
	
	
	function updateField($id,$campo,$value){
		
		$s_sql = "UPDATE ".$this->tabla." SET ".$campo." = '".$this->filtro($value)."' WHERE ".$this->primarykey." = ".$id."";
		
		return $this->ejecutarInsertUpdate($s_sql);
	}
	
	
	function getUltimos($num = 30){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE activo = 1 AND borrado = 0 ORDER BY fecha DESC LIMIT ".$num;
		
		return $this->ejecutarConsulta($s_sql);
	}
	
	function getAleatorio(){
		$s_sql = "SELECT id FROM ".$this->tabla." WHERE activo = 1 AND borrado = 0";
		
		if($this->ejecutarConsulta($s_sql)){
			$a_elementos = array();
			$i = 0;
			foreach($this->resultado as $elemento){
				$a_elementos[] = $elemento["id"];
				$i++;
			}
			$rand = rand(0,$i-1);
			return $this->getById($a_elementos[$rand]);
		}else{
			return false;
		}
	}
	
	
	function buscar($s_texto){
		$s_texto = $this->filtro($s_texto);
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE activo = 1 AND borrado = 0 AND (nombre like '%".$s_texto."%' OR contenido like '%".$s_texto."%')";
		
		return $this->ejecutarConsulta($s_sql);
	}
	
	
	function getByCat($slug_cat){
		
		$s_sql = "SELECT r.nombre as nombre, r.slug as slug FROM ".$this->tabla_otra." r INNER JOIN ".$this->tabla_cruce." cc ON r.id = cc.".$this->cruce_id." INNER JOIN ".$this->tabla." c ON c.id = cc.fk_id_categoria WHERE c.slug = '".$slug_cat."' AND r.activo = 1 AND r.borrado = 0";
		
		return $this->ejecutarConsulta($s_sql);
	}
	
	function getCatById($id){
		$s_sql = "SELECT * FROM ".$this->tabla_cruce." WHERE ".$this->cruce_id." = ".$id."";
		
		return $this->ejecutarConsulta($s_sql);
	}
	
	
	function limpiarCategorias($id){
		$s_sql = "DELETE FROM ".$this->tabla_cruce." WHERE ".$this->cruce_id." = ".$id."";
		
		return $this->ejecutarInsertUpdate($s_sql);
	}
	
	function addCat($id,$id_cat){
		$s_sql = "INSERT INTO ".$this->tabla_cruce." (".$this->cruce_id.",fk_id_categoria) VALUES (".$id.",".$id_cat.")";
		
		return $this->ejecutarInsertUpdate($s_sql);
	}
	
	function getCategoriasByFkId($id){
		$s_sql = "SELECT t.nombre as nombre, t.slug as slug FROM ".$this->tabla_cruce." c INNER JOIN ".$this->tabla." t ON c.fk_id_categoria = t.id  WHERE c.".$this->cruce_id." = ".$id." AND t.activo = 1 AND t.borrado = 0";
		//die($s_sql);
		return $this->ejecutarConsulta($s_sql);
	}
	
	function getLastByUser($id_user){
		$s_sql = "SELECT * FROM ".$this->tabla." WHERE borrado = 0  AND fk_id_usuario = ".$id_user." ORDER BY fecha_creacion DESC LIMIT 2";
		
		return $this->ejecutarConsulta($s_sql);
	}
	
	function getAllActivoOrdenAZ()
    {
		return $this->select("SELECT * FROM ".$this->tabla."  WHERE borrado = 0 AND activo = 1 ORDER BY nombre ASC LIMIT ".$this->config["db.limit"]."");
	}
	
	function getByCatOrdenAZ($slug_cat){
		
		$s_sql = "SELECT r.nombre as nombre, r.slug as slug FROM ".$this->tabla_otra." r INNER JOIN ".$this->tabla_cruce." cc ON r.id = cc.".$this->cruce_id." INNER JOIN ".$this->tabla." c ON c.id = cc.fk_id_categoria WHERE c.slug = '".$slug_cat."' AND r.activo = 1 AND r.borrado = 0 ORDER BY nombre ASC";
		
		return $this->ejecutarConsulta($s_sql);
	}
}//class





