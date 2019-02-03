/*** CFW ***/


function sendLoginForm(){
	if($("#user").val() != "" && $("#pass").val() != ""){
		$("#pass").val(md5($("#pass").val()));
		$("#timezone").val(Intl.DateTimeFormat().resolvedOptions().timeZone);
		$("#formulario").submit();
	}else{
		devolver_mensaje("<p style='color:Red;'>"+text_rellena_ambos_campos+"</p>");
	}
}



function sendRegisterForm(){
	//&& $("#cif").val() != ""
	//&& $("#dni").val() != ""
	if($("#email").val() != "" && $("#pass").val() != "" && $("#razon_social").val() != ""  && $("#direccion").val() != "" && $("#cp").val() != "" && $("#ciudad").val() != "" && $("#fk_pais").val() != 0 && $("#nombre").val() != "" && $("#apellidos").val() != "" && $("#telefono_empresa").val() != ""){
		if($("#confirm_pass").val() == $("#pass").val() && $("#pass").val() != ""){
			if(comprobar_mail($("#email").val())){
				if($("#aceptar_condiciones").is(":checked")){
					$("#pass").val(md5($("#pass").val()));
					$("#confirm_pass").val(md5($("#confirm_pass").val()));
					$("#formulario").submit();
				}else{
					devolver_mensaje("<p style='color:Red;'>"+text_aceptar_terminos+"</p>");
				}
				
			}else{
				devolver_mensaje("<p style='color:Red;'>"+text_email_incorrecto+"</p>");
			}
		}else{
			devolver_mensaje("<p style='color:Red;'>"+text_contrasenas_iguales+"</p>");
		}
	}else{
		devolver_mensaje("<p style='color:Red;'>"+text_rellena_campos_obligatorios+"</p>");
	}
}



function comprobar_mail(s_mail){
	var filter = /[\w-\.]{1,}@([\w-]{1,}\.)*([\w-]{1,}\.)[\w-]{2,4}/;
	if(filter.test(s_mail)){
		return true;
	}else{
		return false;
	}
}



function devolver_mensaje(s_mensaje){
	$("#mensaje").html(s_mensaje);
	$("#mensaje_arriba").html(s_mensaje);
	$("#mensaje_abajo").html(s_mensaje);
}



function sendAForm(s_form){
	$(s_form).submit();
}



function muestraError(){
	alert(text_algun_error);
}



function salir(){
	if(confirm(text_salir)){
		window.location = "/login/logout/";
	}
}



function sendFormUser(){
	if($('#pass1').val() != ''){
		$('#pass1').val(md5($('#pass1').val()));
	}
	if($('#pass2').val() != ''){
		$('#pass2').val(md5($('#pass2').val()));
	}
	$('#formulariouser').submit();
}


