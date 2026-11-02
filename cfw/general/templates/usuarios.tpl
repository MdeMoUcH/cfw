<div>
	<h2>{titulo}</h2>
	<hr/>
	<div id="mensaje">{mensaje}&nbsp;</div>
	<p>{content}</p>
	<br/>
	<hr/>
	<form method="post" action="{urlbase}login/logout" style="display:inline;"><input type="hidden" name="csrf" value="{csrf}" /><input type="submit" value="{text_salir}" /></form>
</div>
