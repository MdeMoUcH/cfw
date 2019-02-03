
<h1>Cuenta Personal</h1>
{content}
<hr/>
<p>Si quieres cambiar alguno de tus datos, modifícalo en el siguiente formulario:</p>
<div style="width:90%;margin-left:60px;margin-top:20px;">
	<form method="POST" name="formulariouser" id="formulariouser">
		{content_user}
		<p><b>Nombre de usuario:</b> <input type="text" value="{nick}" name="nick" id="nick" /></p>
		<p><b>E-mail:</b> <input type="text" value="{email}" name="email" id="email" /></p>
		<p>&nbsp;</p>
		<p><b>Si no quieres cambiar tu contraseña no escribas nada en los siguientes campos:</b></p>
		<p><b>Nueva contraseña:</b> <input type="password" name="pass1" id="pass1" /></p>
		<p><b>Confirmar nueva contraseña:</b> <input type="password" name="pass2" id="pass2" /></p>
		
		<br/><input type="hidden" value="edituser" name="save" id="save" />
		<input style='width:60%;margin-top:32px;' type="submit" class="button" value="Guardar Datos Personales" name="button" id="button" />
	</form>
</div>


