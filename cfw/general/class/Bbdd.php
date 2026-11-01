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

        $this->conexion = @mysqli_connect($this->config["db.host"],$this->config["db.user"],$this->config["db.pass"],$this->config["db.name"]);
        
        if($this->conexion)
        {
            mysqli_set_charset($this->conexion, "utf8");
        }//if
        else
        {
            error_log("CFW: no se pudo conectar a la base de datos: ".mysqli_connect_error());
            $this->errores[] = "No se pudo conectar a la base de datos.";
        }//else
    }//constructor


    function cerrar()
    {
        if($this->conexion)
        {
            mysqli_close($this->conexion);
            $this->conexion = false;
        }//if
    }//fun
    
    function close()
    {
        $this->cerrar();
    }//fun
    


    function ejecutarConsulta($s_sql)
    {
        $this->resultado = array();
        
        if(!$this->conexion)
        {
            $this->errores[] = "Error en la consulta.";
            return false;
        }//if
        
        $result = $this->conexion->query($s_sql);
        
        if($result === false)
        {
            error_log("CFW: error en la consulta: ".mysqli_error($this->conexion));
            $this->errores[] = "Error en la consulta.";
        }//if
        elseif($result instanceof mysqli_result)
        {
            while($aux = mysqli_fetch_array($result))
            {
                $this->resultado[] = $aux;
            }//while
        }//elseif

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
        if(!$this->conexion)
        {
            $this->errores[] = "Error en el insert-update.";
            return false;
        }//if
        
        $b_ok = $this->conexion->query($s_sql);
        if(!$b_ok)
        {
            error_log("CFW: error en el insert-update: ".mysqli_error($this->conexion));
            $this->errores[] = "Error en el insert-update.";
        }//if

        return $b_ok;
    }//fun
    
}//class
