<!--<table id="tabla" name="tabla" width="100%" class="sortable resizable editable tablekit">-->
<div class="panel-body">
<table id="tabla" name="tabla" width="100%" class="table table-striped">
<thead>
<tr>
	<th width="10px" class="noedit th_listado"></th>
	<th width="30px" class="sortfirstasc noedit th_listado" id="id">{text_col_id}</th>
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
	var cfw_tablekit_submit = TableKit.Editable.CellEditor.prototype.submit;
	TableKit.Editable.CellEditor.prototype.submit = function(cell, form){
		form = form ? form : cell.down('form');
		if(form && !$(form).down('input[name="csrf"]')){
			var input = document.createElement("input");
			input.type = "hidden";
			input.name = "csrf";
			input.value = "{csrf}";
			form.appendChild(input);
		}
		return cfw_tablekit_submit.call(this, cell, form);
	};
}
</script>
</div>
