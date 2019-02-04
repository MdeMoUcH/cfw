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
        @session_start();
        
        if(!isset($_SESSION[$this->config["name"]]))
        {
			
            $this->data[$this->config["name"]] = ":)";
            $this->data["time"] = date("Y-m-d H:i:s");
            $this->data["last_time"] = date("Y-m-d H:i:s");
            $this->data["logueado"] = "";
            $this->data["id_usuario"] = "";
            $this->data["admin"] = false;
            $this->data["ip"] = get_ip();
            $this->data["lang"] = $this->config["lang_default"];

            $this->data["paginas"] = 1;
        }//if
        else
        {
			$this->data = $_SESSION;
			
            $this->data["session_id"] = session_id();
            $this->data["last_time"] = date("Y-m-d H:i:s");
        }//else

        $this->actualiza();
    }//fun


    function actualiza()
    {
        $_SESSION = $this->data;
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
        
        if($lang == ""){
			$this->data["lang"] = $this->config["lang_default"];
		}

        $this->actualiza();
    }//fun

	function setUserOut()
    {
        session_destroy();
        $this->data["logueado"] = "";
        $this->data["id_usuario"] = "";
        $this->data["admin"] = false;

        $this->actualiza();
    }//fun

}//class
