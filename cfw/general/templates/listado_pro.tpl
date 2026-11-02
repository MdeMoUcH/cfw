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
</div>
