<?php
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/
 
//Código inicial sacado de aquí:
//http://www.cristalab.com/tutoriales/creacion-y-uso-de-plantillas-o-templates-en-php-c132l/



class Template extends App{
	public $vars;
	public $tpl_file;
	public $mihtml;
	
	function __construct($template_file){
		parent::__construct();
		
		$pos = strpos($template_file,'.tpl');
		if($pos != 0){
			$template_file = substr($template_file,0,$pos);
		}
		
		$b_valido = preg_match('#^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*$#D',$template_file) === 1;
		
		if($b_valido && file_exists($this->config['pathbase'].'cfw/specific/templates/'.$template_file.'.tpl')){
			$this->tpl_file = $this->config['pathbase'].'cfw/specific/templates/'.$template_file.'.tpl';
			$this->set_ini_vars($template_file);
		}elseif($b_valido && file_exists($this->config['pathbase'].'cfw/general/templates/'.$template_file.'.tpl')){
			$this->tpl_file = $this->config['pathbase'].'cfw/general/templates/'.$template_file.'.tpl';
			$this->set_ini_vars($template_file);
		}else{
			http_response_code(404);
			$this->showError('No existe el template: '.$template_file);
		}
	}//fun set_template
	
	
	function set_vars($vars){
		if(isset($this->vars)){
			foreach($vars as $id=>$var){
				$this->vars[$id] = $var;
			}//foreach
		}else{
			$this->vars = $vars;
		}
	}//fun set_vars
	
	
	function set_ini_vars($nombre = ''){
		static $a_ini = array();
		
		$ini_file = $this->my_file_exist($nombre.'.ini');
		if($ini_file != ''){
			if(!isset($a_ini[$ini_file])){
				$a_ini[$ini_file] = parse_ini_file($ini_file);
			}//if
			$this->set_vars($a_ini[$ini_file]);
		}//if
		
		$php_file = $this->my_file_exist($nombre.'.php');
		if($php_file != ''){
			require_once($php_file);
			$this->set_vars(get_array_text());
		}
	}//fun set_vars
	
	
	function my_file_exist($archivo){
		$path = $this->config['pathbase'].'cfw/';
		$lang = $this->session->data['lang'];
		$default_lang = $this->config['lang_default'];
		
		if(file_exists($path.'specific/lang/'.$lang.'/'.$archivo)){
			return $path.'specific/lang/'.$lang.'/'.$archivo;
		}elseif(file_exists($path.'specific/lang/'.$default_lang.'/'.$archivo)){
			return $path.'specific/lang/'.$default_lang.'/'.$archivo;
		}elseif(file_exists($path.'general/lang/'.$lang.'/'.$archivo)){
			return $path.'general/lang/'.$lang.'/'.$archivo;
		}elseif(file_exists($path.'general/lang/'.$default_lang.'/'.$archivo))		{
			return $path.'general/lang/'.$default_lang.'/'.$archivo;
		}else{
			return '';
		}
	}//fun my_require_once
	
	
	function get($vars = ''){
		
		if($vars != ''){
			$this->set_vars($vars);
		}//if
		
		static $a_plantillas = array();
		
		if(!isset($a_plantillas[$this->tpl_file])){
			$s_plantilla = @file_get_contents($this->tpl_file);
			if($s_plantilla === false){
				error_log('CFW: error al abrir la plantilla: '.$this->tpl_file);
				$this->showError('Error al abrir la plantilla.');
			}//if
			$a_plantillas[$this->tpl_file] = $s_plantilla;
		}//if
		
		$a_vars = isset($this->vars) ? $this->vars : array();
		
		//Una sola pasada: los valores no se vuelven a expandir.
		$this->mihtml = preg_replace_callback('#\{([a-z0-9\-_]+)\}#i', function($a_match) use ($a_vars){
			$key = $a_match[1];
			if(isset($a_vars[$key])){
				return is_scalar($a_vars[$key]) ? (string)$a_vars[$key] : "";
			}//if
			//Se lee de $_SESSION porque la copia de este objeto puede estar anticuada.
			if($key === "csrf" && isset($_SESSION["csrf"])){
				return $_SESSION["csrf"];
			}//if
			return "";
		}, $a_plantillas[$this->tpl_file]);
		
		return $this->mihtml;
	}//fun get
	
	
	function show(){
		if(!isset($this->mihtml)){
			$this->get();
		}//if
		
		die($this->mihtml);
	}//fun show
}//class
