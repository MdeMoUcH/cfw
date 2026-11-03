/*** CFW ***/

var b_login_enviado = false;


function sendLoginForm(){
	if(b_login_enviado){
		return;
	}
	if($("#nick").val() != "" && $("#pass").val() != ""){
		//La contraseña sale cifrada (js/cifrado.js); si no se puede, no se envía.
		b_login_enviado = cfwEnviarCifrado(document.getElementById("formulario"));
	}else{
		devolver_mensaje("<p style='color:Red;'>"+text_rellena_ambos_campos+"</p>");
	}
}



function devolver_mensaje(s_mensaje){
	$("#mensaje").html(s_mensaje);
	$("#mensaje_arriba").html(s_mensaje);
	$("#mensaje_abajo").html(s_mensaje);
}



$(function(){
	$("#nick, #pass").on("keydown",function(e){
		if(e.key === "Enter"){
			e.preventDefault();
			sendLoginForm();
		}
	});
});



$(window).on("pageshow",function(e){
	if(e.originalEvent.persisted){
		b_login_enviado = false;
	}
});
