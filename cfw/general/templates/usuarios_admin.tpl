<div>
<div class="panel-title fondo_claro"><h1>{titulo}</h1></div>
	<hr/>
	<div id="mensaje">{mensaje}&nbsp;</div>
	<p>{content}</p>
	<br/>
	<hr/>
	<form method="post" action="{urlbase}login/logout" style="display:inline;"><input type="hidden" name="csrf" value="{csrf}" /><input type="submit" value="{text_logout}" /></form>&nbsp;|&nbsp;<a href="{urlbase}admin/users">{text_enlace_lista}</a>&nbsp;|&nbsp;<a href="{urlbase}admin/users/form">{text_crear_usuario}</a>
</div>
