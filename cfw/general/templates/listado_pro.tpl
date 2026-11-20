<!--<table id="tabla" name="tabla" width="100%" class="sortable resizable editable tablekit">-->
<div class="panel-body">
<table id="tabla" name="tabla" width="100%" class="table table-striped">
<thead>
<tr>
	<th width="30px" class="noedit th_listado"></th>
	<th width="70px" class="sortfirstasc noedit th_listado" id="id">{text_col_id}</th>
	<th id="nombre" class="noedit th_listado">{text_col_nombre}</th>
	<th width="120px" class="noedit th_listado">{text_col_acciones}</th>
</tr>
</thead>
{elementos}
</table>
<script type="text/javascript">
function borrar(s_url){
	if(confirm({confirmar_borrado_json})){
		var form = document.createElement("form");
		form.method = "post";
		form.action = s_url;
		var input = document.createElement("input");
		input.type = "hidden";
		input.name = "csrf";
		input.value = "{csrf}";
		form.appendChild(input);
		document.body.appendChild(form);
		form.submit();
	}
}
</script>
<script type="text/javascript">
//Edición en línea de TableKit (tablas con clase editable): tablekit.js no envía el csrf y se añade al formulario de la celda.
if(window.TableKit && TableKit.Editable && TableKit.Editable.CellEditor){
	//Solo si nadie lo ha configurado: '/' es el valor por defecto de TableKit.
	if(TableKit.options.editAjaxURI === '/'){
		TableKit.options.editAjaxURI = {editfield_url_json};
	}
	//Sin URL (navegadores antiguos) no se envía el token: el POST da 403 y la celda se restaura.
	var cfw_mismo_origen = function(s_url){
		try{
			return new URL(s_url, document.baseURI || window.location.href).origin === window.location.origin;
		}catch(e){
			return false;
		}
	};
	var cfw_tablekit_submit = TableKit.Editable.CellEditor.prototype.submit;
	TableKit.Editable.CellEditor.prototype.submit = function(cell, form){
		var op = this.options;
		var table = cell.up('table');
		form = form ? form : cell.down('form');
		//La misma URL que usa tablekit.js.
		var s_url = op.ajaxURI || TableKit.option('editAjaxURI', table.id)[0];
		if(form && cfw_mismo_origen(s_url) && !$(form).down('input[name="csrf"]')){
			var input = document.createElement("input");
			input.type = "hidden";
			input.name = "csrf";
			input.value = "{csrf}";
			form.appendChild(input);
		}
		var s_previo = TableKit.getCellData(cell).htmlContent;
		//tablekit.js escribe postBody y onComplete en las opciones, que son compartidas: se le pasa una copia.
		var previas = op.ajaxOptions;
		var copia = Object.extend({}, previas || TableKit.option('editAjaxOptions', table.id)[0]);
		//Solo se pinta la respuesta en la celda si es un éxito de verdad.
		var b_pintar = false;
		var cfw_restaurar = function(s_msg){
			//Ajax.Updater escribe en la celda después de onSuccess y onFailure.
			setTimeout(function(){
				cell.innerHTML = s_previo;
				alert(s_msg);
			}, 0);
		};
		//Clasifica la respuesta: con un error restaura la celda y devuelve false; si no, deja pintarla.
		//Prototype da por buenos el estado 0 (sin respuesta) y la respuesta tras una redirección (el login).
		var cfw_tratar = function(r){
			var s_msg = '';
			if(!r.status){
				s_msg = {error_conexion_json};
			}else{
				try{
					var o_url = new URL(r.request.url, document.baseURI || window.location.href);
					o_url.hash = '';
					if(r.transport.responseURL && r.transport.responseURL !== o_url.href){
						s_msg = {sesion_caducada_json};
					}
				}catch(e){}
				if(s_msg === '' && !r.request.success()){
					s_msg = {error_edicion_json};
					//El 400 trae el motivo escapado; DOMParser lo decodifica sin ejecutar ni cargar nada.
					if(r.status == 400){
						s_msg = new DOMParser().parseFromString(r.responseText, 'text/html').documentElement.textContent;
					}else if(r.status == 401){
						s_msg = {sesion_caducada_json};
					}
				}
			}
			if(s_msg !== ''){
				cfw_restaurar(s_msg);
				return false;
			}
			b_pintar = true;
			return true;
		};
		var cfw_on_failure = copia.onFailure;
		copia.onFailure = function(r, json){
			cfw_tratar(r);
			if(typeof cfw_on_failure === 'function'){
				cfw_on_failure(r, json);
			}
		};
		var cfw_on_success = copia.onSuccess;
		copia.onSuccess = function(r, json){
			if(!cfw_tratar(r)){
				if(typeof cfw_on_failure === 'function'){
					cfw_on_failure(r, json);
				}
				return;
			}
			if(typeof cfw_on_success === 'function'){
				cfw_on_success(r, json);
			}
		};
		//Un on<estado> propio tiene prioridad en Prototype sobre onSuccess y onFailure: se comprueba antes de llamarlo.
		Object.keys(copia).forEach(function(s_clave){
			var propio = copia[s_clave];
			if(/^on\d+$/.test(s_clave) && typeof propio === 'function'){
				copia[s_clave] = function(r, json){
					cfw_tratar(r);
					propio(r, json);
				};
			}
		});
		//tablekit.js pisa onComplete: uno propio se llama después del suyo. Solo en la copia.
		var cfw_on_complete = copia.onComplete;
		if(typeof cfw_on_complete === 'function'){
			var cfw_complete_tablekit = null;
			Object.defineProperty(copia, 'onComplete', {
				enumerable: true,
				configurable: true,
				get: function(){
					return function(r, json){
						if(typeof cfw_complete_tablekit === 'function'){
							cfw_complete_tablekit(r, json);
						}
						cfw_on_complete(r, json);
					};
				},
				set: function(f){
					cfw_complete_tablekit = f;
				}
			});
		}
		var cfw_insertion = copia.insertion;
		copia.insertion = function(receiver, s_texto){
			if(!b_pintar){
				return;
			}
			if(cfw_insertion && Object.isString(cfw_insertion)){
				var o_insertar = {};
				o_insertar[cfw_insertion] = s_texto;
				receiver.insert(o_insertar);
			}else if(typeof cfw_insertion === 'function'){
				cfw_insertion(receiver, s_texto);
			}else{
				receiver.update(s_texto);
			}
		};
		op.ajaxOptions = copia;
		try{
			return cfw_tablekit_submit.call(this, cell, form);
		}finally{
			op.ajaxOptions = previas;
		}
	};
}
</script>
</div>
