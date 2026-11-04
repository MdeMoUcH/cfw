/*** CFW ***/

//Envío de contraseñas cifradas con RSA-OAEP (node-forge) y la clave pública del servidor (Page::prepararCifrado).
//Los campos visibles no tienen name: si algo falla, no sale ninguna contraseña.


function cfwEnviarCifrado(o_form){
	if(!o_form || o_form.cfw_enviado){
		return false;
	}
	var a_textos = window.cfw_textos_cifrado || {};
	//Sin getRandomValues, forge usaría entropía débil.
	if(!window.forge || !window.cfw_clave_publica || !window.cfw_nonce || !window.crypto || !window.crypto.getRandomValues){
		cfwMensajeCifrado(o_form,a_textos.no_disponible);
		return false;
	}

	var a_valores = [];
	try{
		var o_clave = forge.pki.publicKeyFromPem(cfw_clave_publica);
		//Capacidad de RSA-OAEP con SHA-1: k - 2*20 - 2 bytes.
		var i_max = Math.ceil(o_clave.n.bitLength()/8) - 42;
		var a_campos = o_form.querySelectorAll("input[data-cifrar]");
		for(var i = 0; i < a_campos.length; i++){
			var s_campo = a_campos[i].getAttribute("data-cifrar");
			var s_valor = "";
			if(a_campos[i].value !== ""){
				var s_texto = forge.util.encodeUtf8("1|"+cfw_nonce+"|"+s_campo+"|"+a_campos[i].value);
				if(s_texto.length > i_max){
					cfwMensajeCifrado(o_form,a_textos.pass_no_valida);
					return false;
				}
				s_valor = forge.util.encode64(o_clave.encrypt(s_texto,"RSA-OAEP"));
			}
			a_valores.push([s_campo,s_valor]);
		}
	}catch(e){
		cfwMensajeCifrado(o_form,a_textos.no_disponible);
		return false;
	}

	var a_anteriores = o_form.querySelectorAll("input[data-cifrado]");
	for(var j = 0; j < a_anteriores.length; j++){
		a_anteriores[j].parentNode.removeChild(a_anteriores[j]);
	}
	for(var k = 0; k < a_valores.length; k++){
		var o_oculto = document.createElement("input");
		o_oculto.type = "hidden";
		o_oculto.name = a_valores[k][0];
		o_oculto.value = a_valores[k][1];
		o_oculto.setAttribute("data-cifrado","1");
		o_form.appendChild(o_oculto);
	}

	o_form.cfw_enviado = true;
	//No dispara submit: no vuelve a pasar por el escuchador de abajo.
	HTMLFormElement.prototype.submit.call(o_form);
	return true;
}



function cfwMensajeCifrado(o_form,s_texto){
	var o_mensaje = o_form.previousElementSibling;
	if(!o_mensaje || !o_mensaje.hasAttribute("data-cifrado-mensaje")){
		o_mensaje = document.createElement("p");
		o_mensaje.style.color = "Red";
		o_mensaje.className = "mensaje mensaje_Red";
		o_mensaje.setAttribute("data-cifrado-mensaje","1");
		o_form.parentNode.insertBefore(o_mensaje,o_form);
	}
	o_mensaje.textContent = s_texto || "";
}



document.addEventListener("submit",function(e){
	var o_form = e.target;
	if(o_form && o_form.querySelector && o_form.querySelector("input[data-cifrar]")){
		e.preventDefault();
		cfwEnviarCifrado(o_form);
	}
});



//Al volver atrás desde la caché se puede reenviar (el servidor dirá si el formulario ha caducado).
window.addEventListener("pageshow",function(e){
	if(e.persisted){
		var a_forms = document.forms;
		for(var i = 0; i < a_forms.length; i++){
			a_forms[i].cfw_enviado = false;
		}
	}
});
