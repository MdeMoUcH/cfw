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
    public $resultado;
    public $conexion;
    
    function __construct()
    {
		parent::__construct();
		
        $this->resultado = array();

        //Desde PHP 8.1 mysqli lanza excepciones por defecto; aquí se trabaja con los false.
        mysqli_report(MYSQLI_REPORT_OFF);
        
        $this->conexion = @mysqli_connect($this->config["db.host"],$this->config["db.user"],$this->config["db.pass"],$this->config["db.name"]);
        
        if($this->conexion)
        {
            mysqli_set_charset($this->conexion, "utf8");
        }//if
        else
        {
            error_log("CFW: no se pudo conectar a la base de datos: ".mysqli_connect_error());
            $this->errores[] = t("text_bd_conexion");
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
            $this->errores[] = t("text_bd_consulta");
            return false;
        }//if
        
        $result = $this->conexion->query($s_sql);
        
        if($result === false)
        {
            error_log("CFW: error en la consulta: ".mysqli_error($this->conexion));
            $this->errores[] = t("text_bd_consulta");
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
            $this->errores[] = t("text_bd_escritura");
            return false;
        }//if
        
        $b_ok = $this->conexion->query($s_sql);
        if(!$b_ok)
        {
            error_log("CFW: error en el insert-update: ".mysqli_error($this->conexion));
            $this->errores[] = t("text_bd_escritura");
        }//if

        return $b_ok;
    }//fun
    
}//class
