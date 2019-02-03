<?
/***************
 * 
 * CFW - Framework MVC en PHP
 * MdeMoUcH - mdemouch@gmail.com
 * 2010
 * 
 ***************/


$s_path = '';
require_once($s_path.'cfw/general/utils/functions.php');

if(file_exists($s_path.'cfw/specific/utils/functions.php')){
	require_once($s_path.'cfw/specific/utils/functions.php');
}//if

if(file_exists($s_path.'cfw/specific/utils/PHPMailer.php')){
	require_once($s_path.'cfw/specific/utils/PHPMailer.php');
	require_once($s_path.'cfw/specific/utils/SMTP.php');
}//if

/*
if(file_exists($s_path.'cfw/specific/utils/mpdf/mpdf.php')){
	require_once($s_path.'cfw/specific/utils/mpdf/mpdf.php');
}//if*/

$a_clases = array('AppConf','App','Msession','Bbdd','Template','Page',
				'Error','Orm','Form','AdminHelper');

$a_orms = array('Usuarios','DinamicOrm');
//die(getcwd());

foreach($a_clases as $archivo){
	my_require_once('class/'.$archivo);
}//foreach

foreach($a_orms as $archivo){
	my_require_once('orm/'.$archivo);
}//foreach


function my_require_once($nombre){
	$s_path = '';
	if(file_exists($s_path.'cfw/specific/'.$nombre.'.php')){
		require_once($s_path.'cfw/specific/'.$nombre.'.php');
	}else{
		require_once($s_path.'cfw/general/'.$nombre.'.php');
	}//else
}//fun my_require_once
