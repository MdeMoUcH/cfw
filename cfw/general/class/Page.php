<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/




class Page extends App
{
	function __construct()
	{
		parent::__construct();
		$this->vars  = array();
		$this->vars["charset"] = $this->config["charset"];
		$this->vars["app_name"] = $this->config["name"];
		$this->vars["page_description"] = "";
		$this->vars["page_keywords"] = "";
		$this->vars["css"] = "";
		$this->vars["js"] = "";
		$this->vars["menu"] = "";
		//$this->vars["menu"] = $this->get_menu();
		$this->vars["body"] = "";
		
		//$this->add_include("css",$this->config["urlbase"]."css/style.css");
		$this->add_include("css",$this->config["urlbase"]."css/tablas.css");
	}//fun __construct
	
	
	protected function redirigir($url)
	{
		header("Location: ".$url);
		exit;
	}//fun redirigir
	
	
	protected function exigirUsuario()
	{
		$this->revalidarSesion(false);
	}//fun exigirUsuario
	
	
	protected function exigirAdmin()
	{
		$this->revalidarSesion(true);
	}//fun exigirAdmin
	
	
	//Comprueba en la BD que el usuario de la sesión sigue activo y, si se pide, que sigue siendo administrador.
	private function revalidarSesion($b_admin)
	{
		$s_destino = $this->config["urlbase"]."admin/login";
		
		if(!$this->session->isUserLogged() || ($b_admin && !$this->session->isUserAdmin()))
		{
			$this->redirigir($s_destino);
		}//if
		
		$orm = new Usuarios();
		$a_usuario = $orm->getActivoById($this->session->data["id_usuario"]);
		$b_error = count($orm->errores) >= 1;
		$orm->cerrar();
		
		if($b_error)
		{
			//Un fallo de la BD no debe cerrar la sesión.
			http_response_code(503);
			$this->showError("El servicio no está disponible en este momento.");
		}//if
		
		if(!$a_usuario || ($b_admin && $a_usuario["fk_tipo_usuario"] != 1))
		{
			$this->session->setUserOut();
			$this->redirigir($s_destino);
		}//if
	}//fun revalidarSesion
	
	
	function set_vars($vars)
	{
		if(is_array($vars))
		{
			foreach($vars as $id=>$var)
			{
				$this->vars[$id] = $var;
			}//foreach
		}//if
    }//fun set_vars
	
	
	function show($tpl_aux)
	{
		
		$this->session->data["paginas"]++;
		$this->session->actualiza();
		
		$this->set_vars($tpl_aux->vars);
		$this->vars["body"] = $tpl_aux->get();
		
		if(!isset($this->vars["page_title"]))
		{
			$this->vars["page_title"] = $this->config["name"];
		}//if
		if($this->vars["page_description"] == "")
		{
			$this->vars["page_description"] = $this->config["description"];
		}//if
		if($this->vars["page_keywords"] == "")
		{
			$this->vars["page_keywords"] = $this->config["keywords"];
		}//if
		
		if(!$this->session->isUserAdmin() && @$this->config["analytics"] != "" && !$this->config["desarrollo"]){
			$tpl_analytics = new Template("html/analytics");
			$tpl_analytics->vars["codigo"] = $this->config["analytics"];
			$this->vars["analytics"] = $tpl_analytics->get();
		}//if
		
		$this->vars["urlbase"] = $this->config["urlbase"];
		
		$tpl = new Template("cascara");
		
		$tpl->set_vars($this->vars);
		
		if(count($this->errores) >= 1)
		{
			$this->showError();
		}//if
		
		$tpl->show();
	}//fun show
	
	
	function get_menu($selected = "main"){
		$tpl = new Template("menu");
		$vars = array();
		$vars["class_".$selected] = $tpl->vars["class"];
		
		if($this->session->isUserAdmin()){
			$vars["admin"] = "Administrador";
			$vars["url_admin"] = "admin";
		}
		
		
		$vars["enlaces_usuario"] = "";
		if($this->session->isUserLogged()){
			$vars["enlaces_usuario"] .= '<li><a href="'.$this->config["urlbase"].'admin/cuenta">Cuenta</a></li>';
		}
		if($this->session->isUserAdmin()){
			$vars["enlaces_usuario"] .= '<li><a href="'.$this->config["urlbase"].'admin/users">Usuarios</a></li>';
		}
		
		$tpl->set_vars($vars);
		//muere($tpl->vars,1);
		$tpl->set_vars($this->config);
		
		return $tpl->get();
	}//fun get_menu
	
	
	function add_include($tipo, $ruta){
		$tpl = new Template("html/".$tipo);
		
		$vars["ruta"] = $ruta;
		$tpl->set_vars($vars);
		
		$this->vars[$tipo] .= $tpl->get();
	}//fun get_menu
}//class
