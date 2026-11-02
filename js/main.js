/*** CFW ***/

var text_rellena_ambos_campos = "Rellena ambos campos.";
var b_login_enviado = false;


function sendLoginForm(){
	if(b_login_enviado){
		return;
	}
	if($("#nick").val() != "" && $("#pass").val() != ""){
		var o_hash = $("#pass_md5");
		if(o_hash.length == 0){
			o_hash = $("<input>",{type:"hidden",id:"pass_md5",name:"pass"}).appendTo("#formulario");
		}
		o_hash.val(md5($("#pass").val()));
		$("#pass").removeAttr("name");
		b_login_enviado = true;
		$("#formulario").trigger("submit");
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
