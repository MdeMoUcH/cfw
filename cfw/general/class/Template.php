<?
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
	
	function Template($template_file){
		parent::__construct();
		
		$pos = strpos($template_file,'.tpl');
		if($pos != 0){
			$template_file = substr($template_file,0,$pos);
		}
		
		if(file_exists($this->config['pathbase'].'cfw/specific/templates/'.$template_file.'.tpl')){
			$this->tpl_file = $this->config['pathbase'].'cfw/specific/templates/'.$template_file.'.tpl';
			$this->set_ini_vars($template_file);
		}elseif(file_exists($this->config['pathbase'].'cfw/general/templates/'.$template_file.'.tpl')){
			$this->tpl_file = $this->config['pathbase'].'cfw/general/templates/'.$template_file.'.tpl';
			$this->set_ini_vars($template_file);
		}else{
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
		$ini_file = $this->my_file_exist($nombre.'.ini');
		if($ini_file != ''){
			$this->set_vars(parse_ini_file($ini_file));
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
		
		if(!($this->fd = @fopen($this->tpl_file, 'r'))){
			$this->showError('Error al abrir la plantilla: '.$this->tpl_file);
		}else{
			$this->template_file = fread($this->fd, filesize($this->tpl_file));
			fclose($this->fd);
			$this->mihtml = $this->template_file;
			$this->mihtml = str_replace ("'", "\'", $this->mihtml);
			$this->mihtml = preg_replace('#\{([a-z0-9\-_]*?)\}#is', "' . $\\1 . '", $this->mihtml);
			if(isset($this->vars)){
				reset($this->vars);
				foreach($this->vars as $key=>$val){
					$$key = $val;
				}//while
			}//if
			
			@eval("\$this->mihtml = '$this->mihtml';");
			if(isset($this->vars)){
				reset($this->vars);
				foreach($this->vars as $key=>$val){
					unset($$key);
				}//while
			}//if
			$this->mihtml = str_replace ("\'", "'", $this->mihtml);
			return $this->mihtml;
		}
	}//fun get
	
	
	function show(){
		if(!isset($this->mihtml)){
			$this->get();
		}//if
		
		die($this->mihtml);
	}//fun show
}//class
