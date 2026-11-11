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
	public $vars;
	//Motivo del último fallo de leerPassCifradas: "clave" (del servidor) o "dato" (caducado, repetido o manipulado).
	protected $s_fallo_cifrado = "";
	//Solo la pone revalidarSesion cuando la BD confirma que es administrador; index.php la exige en AdminHelper.
	private $b_admin_revalidado = false;
	
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
		$this->add_include("css",$this->config["urlbase"]."css/tablas.css?v=2");
	}//fun __construct
	
	
	protected function redirigir($url)
	{
		header("Location: ".$url);
		exit;
	}//fun redirigir
	
	
	//exigirUsuario y exigirAdmin son final: una subclase no puede saltarse la revalidación.
	final protected function exigirUsuario()
	{
		$this->revalidarSesion(false);
	}//fun exigirUsuario
	
	
	final protected function exigirAdmin()
	{
		$this->revalidarSesion(true);
	}//fun exigirAdmin
	
	
	//Comprueba en la BD que el usuario de la sesión sigue activo y, si se pide, que sigue siendo administrador.
	private function revalidarSesion($b_admin)
	{
		$s_destino = $this->config["urlbase"]."admin/login";
		
		//Los exit cortan la petición aunque una subclase redefina redirigir o showError sin terminar.
		if(!$this->session->isUserLogged() || ($b_admin && !$this->session->isUserAdmin()))
		{
			$this->redirigir($s_destino);
			exit;
		}//if
		
		$orm = new Usuarios();
		$a_usuario = $orm->getActivoById($this->session->data["id_usuario"]);
		$b_error = count($orm->errores) >= 1;
		$orm->cerrar();
		
		if($b_error)
		{
			//Un fallo de la BD no debe cerrar la sesión.
			http_response_code(503);
			$this->showError(t("text_servicio_no_disponible"));
			exit;
		}//if
		
		if(!$a_usuario || ($b_admin && $a_usuario["fk_tipo_usuario"] != 1))
		{
			$this->session->setUserOut();
			$this->redirigir($s_destino);
			exit;
		}//if
		
		if($a_usuario["fk_idioma"] !== $this->session->data["lang"])
		{
			$this->session->setLang($a_usuario["fk_idioma"]);
		}//if
		
		if($b_admin)
		{
			$this->b_admin_revalidado = true;
		}//if
	}//fun revalidarSesion
	
	
	//final: una subclase no puede fingir que ha comprobado el administrador.
	final public function adminRevalidado()
	{
		return $this->b_admin_revalidado === true;
	}//fun adminRevalidado
	
	
	//Guarda la clave, no el texto: tras cambiar de idioma debe pintarse en el nuevo.
	protected function guardarFlash($s_color, $s_clave)
	{
		$this->session->data["flash"] = array("color" => $s_color, "clave" => $s_clave);
		$this->session->actualiza();
	}//fun guardarFlash
	
	
	protected function leerFlash()
	{
		if(!isset($this->session->data["flash"]))
		{
			return "";
		}//if
		
		$a_flash = $this->session->data["flash"];
		unset($this->session->data["flash"]);
		$this->session->actualiza();
		
		$tpl = new Template("html/p_color");
		$tpl->vars["color"] = $a_flash["color"];
		$tpl->vars["text"] = t($a_flash["clave"]);
		return $tpl->get();
	}//fun leerFlash
	
	
	//Para los formularios con campos password_cifrado. Devuelve el aviso que se pinta junto al formulario.
	protected function prepararCifrado()
	{
		$tpl = new Template("html/p_color");
		$tpl->vars["color"] = "Red";
		
		$s_publica = Cifrado::clavePublica();
		if($s_publica === false)
		{
			$tpl->vars["text"] = t("text_cifrado_no_disponible");
			return $tpl->get();
		}//if
		
		$a_vars = array("cfw_clave_publica" => $s_publica,
						"cfw_nonce" => $this->session->nuevoNonce(),
						"cfw_textos_cifrado" => array("no_disponible" => t("text_cifrado_no_disponible"), "pass_no_valida" => t("text_pass_no_valida")));
		foreach($a_vars as $s_nombre=>$valor)
		{
			$tpljs = new Template("html/js_var");
			$tpljs->vars["nombre"] = $s_nombre;
			$tpljs->vars["valor"] = json_js($valor);
			$this->vars["js"] .= $tpljs->get();
		}//foreach
		$this->add_include("js",$this->config["urlbase"]."js/forge-1.4.2.min.js");
		$this->add_include("js",$this->config["urlbase"]."js/cifrado.js?v=2");
		
		$tpl->vars["text"] = t("text_requiere_javascript");
		return "<noscript>".$tpl->get()."</noscript>";
	}//fun prepararCifrado
	
	
	//Devuelve campo => contraseña, con "" en los que llegan vacíos, o false. El nonce se gasta una vez por petición.
	protected function leerPassCifradas($a_campos)
	{
		$this->s_fallo_cifrado = "";
		$a_pass = array();
		$b_hay = false;
		foreach($a_campos as $s_campo)
		{
			$a_pass[$s_campo] = "";
			if(isset($_POST[$s_campo]) && $_POST[$s_campo] !== "")
			{
				$b_hay = true;
			}//if
		}//foreach
		
		if(!$b_hay)
		{
			return $a_pass;
		}//if
		
		if(!Cifrado::clavePrivada())
		{
			$this->s_fallo_cifrado = "clave";
			return false;
		}//if
		
		$b_ok = true;
		$s_nonce = null;
		foreach($a_campos as $s_campo)
		{
			$valor = isset($_POST[$s_campo]) ? $_POST[$s_campo] : "";
			if($valor === "")
			{
				continue;
			}//if
		
			$s_claro = Cifrado::descifrar($valor);
			$a_partes = is_string($s_claro) ? explode("|",$s_claro,4) : array();
			if(count($a_partes) !== 4 || $a_partes[0] !== "1" || $a_partes[2] !== $s_campo || ($s_nonce !== null && $a_partes[1] !== $s_nonce))
			{
				$b_ok = false;
				continue;
			}//if
		
			$s_nonce = $a_partes[1];
			$a_pass[$s_campo] = $a_partes[3];
		}//foreach
		
		$b_nonce = $s_nonce !== null && $this->session->consumirNonce($s_nonce);
		if(!$b_ok || !$b_nonce)
		{
			$this->s_fallo_cifrado = "dato";
			return false;
		}//if
		
		return $a_pass;
	}//fun leerPassCifradas
	
	
	//Mensaje para un fallo de leerPassCifradas.
	protected function textoFalloCifrado()
	{
		return t($this->s_fallo_cifrado === "clave" ? "text_problema_reintentar" : "text_formulario_caducado");
	}//fun textoFalloCifrado
	
	
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
		
		//Con la sesión del momento: tras un login sale y tras un logout no.
		if(!isset($tpl_aux->vars["boton_salir"]))
		{
			$tpl_aux->vars["boton_salir"] = "";
			if($this->session->isUserLogged())
			{
				$tpl_salir = new Template("html/form_salir");
				$tpl_salir->vars["urlbase"] = $this->config["urlbase"];
				$tpl_aux->vars["boton_salir"] = $tpl_salir->get();
			}//if
		}//if
		
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
		$this->vars["lang_actual"] = GestorIdiomas::actual();
		
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
		$vars["class_".$selected] = 'class="active" ';
		
		if($this->session->isUserAdmin()){
			$vars["admin"] = t("text_menu_admin");
			$vars["url_admin"] = "admin";
		}
		
		
		$vars["enlaces_usuario"] = "";
		if($this->session->isUserLogged()){
			$vars["enlaces_usuario"] .= '<li '.($selected === "cuenta" ? 'class="active" ' : '').'><a href="'.$this->config["urlbase"].'admin/cuenta">'.t("text_menu_cuenta").'</a></li>';
		}
		if($this->session->isUserAdmin()){
			$vars["enlaces_usuario"] .= '<li '.($selected === "usuarios" ? 'class="active" ' : '').'><a href="'.$this->config["urlbase"].'admin/users">'.t("text_usuarios").'</a></li>';
			$vars["enlaces_usuario"] .= '<li '.($selected === "idiomas" ? 'class="active" ' : '').'><a href="'.$this->config["urlbase"].'admin/idiomas">'.t("text_menu_idiomas").'</a></li>';
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
