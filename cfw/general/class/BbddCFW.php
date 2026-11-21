<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 
 * 2010
 * 
 ***************/



class BbddCFW extends App
{
    public $resultado;
    public $conexion;
    //Segundos; el de lectura cubre también el saludo del servidor y cada consulta.
    const TIEMPO_CONEXION = 5;
    const TIEMPO_LECTURA = 30;
    
    function __construct()
    {
		parent::__construct();
		
        $this->resultado = array();

        //Desde PHP 8.1 mysqli lanza excepciones por defecto; aquí se trabaja con los false.
        mysqli_report(MYSQLI_REPORT_OFF);
        
        $this->conexion = mysqli_init();
        if(!$this->conexion
            || !mysqli_options($this->conexion, MYSQLI_OPT_CONNECT_TIMEOUT, self::TIEMPO_CONEXION)
            || !mysqli_options($this->conexion, MYSQLI_OPT_READ_TIMEOUT, self::TIEMPO_LECTURA)
            || !@mysqli_real_connect($this->conexion, $this->config["db.host"], $this->config["db.user"], $this->config["db.pass"], $this->config["db.name"]))
        {
            error_log("CFW: no se pudo conectar a la base de datos (error ".mysqli_connect_errno().")");
            $this->conexion = false;
            $this->errores[] = t("text_bd_conexion");
        }//if
        elseif(!mysqli_set_charset($this->conexion, "utf8"))
        {
            error_log("CFW: no se pudo fijar el juego de caracteres de la base de datos (error ".mysqli_errno($this->conexion).")");
            mysqli_close($this->conexion);
            $this->conexion = false;
            $this->errores[] = t("text_bd_conexion");
        }//elseif
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
            error_log("CFW: error en la consulta (error ".mysqli_errno($this->conexion).")");
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
            error_log("CFW: error en el insert-update (error ".mysqli_errno($this->conexion).")");
            $this->errores[] = t("text_bd_escritura");
        }//if

        return $b_ok;
    }//fun
    
}//class
