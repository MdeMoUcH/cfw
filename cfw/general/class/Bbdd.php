<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 
 * 2010
 * 
 ***************/



class Bbdd extends App
{
    function __construct()
    {
		parent::__construct();
		
        $this->resultado = array();

        $this->conexion = mysqli_connect($this->config["db.host"],$this->config["db.user"],$this->config["db.pass"],$this->config["db.name"])
                or $this->errores[] = "No se pudo conectar a la base de datos (".mysqli_error($this->conexion).")";

        //mysqli_select_db($this->config["db.name"]) or $this->errores[] = "No se pudo seleccionar la base de datos (".mysqli_error().")";
    }//constructor


    function cerrar()
    {
        mysqli_close($this->conexion);
    }//fun
    
    function close()
    {
        mysqli_close($this->conexion);
    }//fun
    


    function ejecutarConsulta($s_sql)
    {
        $result = $this->conexion->query($s_sql)
				or $this->errores[] = "Error en la consulta. (".mysqli_error($this->conexion).")";

        $this->resultado = array();

        while(@$aux = mysqli_fetch_array($result))
        {
            $this->resultado[] = $aux;
        }//while

        //$this->cerrar();

        if(count($this->resultado) >= 1)
        {
            return true;
        }
        else
        {
            return false;
        }//else
    }//fun
    
    function consulta($s_sql){
		$this->ejecutarConsulta($s_sql);
	}


    function ejecutarInsertUpdate($s_sql)
    {
        $b_ok = $this->conexion->query($s_sql);
        if(!$b_ok)
        {
            $this->errores[] = "Error en el insert-update. (".mysqli_error($this->conexion).")";
        }//if

        return $b_ok;
    }//fun
    
}//class
