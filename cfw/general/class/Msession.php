<?php
/*
 * Sessiones La Gran M
 * La Gran M | 2010-10-02
 * lagranm.com
 */

class Msession extends AppConf
{
	public $data;
	
    function __construct()
    {
		parent::__construct();
		
        if(session_status() !== PHP_SESSION_ACTIVE)
        {
            ini_set("session.use_strict_mode", "1");
            session_set_cookie_params(array(
                "lifetime" => 0,
                "path" => "/",
                "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
                "httponly" => true,
                "samesite" => "Lax"
            ));
            session_start();
        }//if
        
        if(!isset($_SESSION[$this->config["name"]]))
        {
            $this->iniciarDatos();
        }//if
        else
        {
			$this->data = $_SESSION;
			
            $this->data["session_id"] = session_id();
            $this->data["last_time"] = date("Y-m-d H:i:s");
        }//else
        
        if(empty($this->data["csrf"]))
        {
            $this->nuevoCsrf();
        }//if

        $this->actualiza();
    }//fun
    
    
    function iniciarDatos()
    {
        $this->data = array();
        $this->data[$this->config["name"]] = ":)";
        $this->data["time"] = date("Y-m-d H:i:s");
        $this->data["last_time"] = date("Y-m-d H:i:s");
        $this->data["logueado"] = "";
        $this->data["id_usuario"] = "";
        $this->data["admin"] = false;
        $this->data["ip"] = get_ip();
        $this->data["lang"] = $this->config["lang_default"];

        $this->data["paginas"] = 1;
    }//fun


    function actualiza()
    {
        $_SESSION = $this->data;
    }//fun
    
    
    function nuevoCsrf()
    {
        $this->data["csrf"] = bin2hex(random_bytes(32));
    }//fun
    
    
    function comprobarCsrf($s_token)
    {
        return is_string($s_token) && hash_equals($this->data["csrf"], $s_token);
    }//fun
    

    function isUserAdmin()
    {
        if($this->data["logueado"] != "" && $this->data["admin"])
        {
            return true;
        }//if

        return false;
    }//fun


    function isUserLogged()
    {
        if($this->data["logueado"] != "")
        {
            return true;
        }//if

        return false;
    }//fun


    function setUserLogged($s_nick_user,$id_user,$tipo_user = 0,$lang = "")
    {
        session_regenerate_id(true);
        
        $this->data["session_id"] = session_id();
        $this->data["logueado"] = $s_nick_user;
        $this->data["id_usuario"] = $id_user;
        if($tipo_user != 1)
        {
            $this->data["admin"] = false;
        }//if
        else
        {
            $this->data["admin"] = true;
        }//else
        
        //GestorIdiomas decide en cada lectura si este idioma sigue activo.
        if(is_string($lang) && preg_match('/^[a-z]{2}$/D', $lang)){
			$this->data["lang"] = $lang;
		}else{
			$this->data["lang"] = $this->config["lang_default"];
		}
		
        $this->nuevoCsrf();
        $this->actualiza();
    }//fun

	//Solo la sesión: quien llama guarda el idioma en la BD.
	function setLang($s_codigo)
	{
		if(is_string($s_codigo) && preg_match('/^[a-z]{2}$/D', $s_codigo))
		{
			$this->data["lang"] = $s_codigo;
		}//if
		else
		{
			$this->data["lang"] = $this->config["lang_default"];
		}//else

		$this->actualiza();
	}//fun

	function setUserOut()
    {
        //session_destroy no sirve: el siguiente session_start reutilizaría el id.
        $_SESSION = array();
        session_regenerate_id(true);
        
        $this->iniciarDatos();
        $this->nuevoCsrf();
        $this->actualiza();
    }//fun

}//class
