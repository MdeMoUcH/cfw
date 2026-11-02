
<h1>{text_cuenta_titulo}</h1>
{content}
{form_idioma}
<hr/>
<p>{text_cuenta_intro}</p>
<div style="width:90%;margin-left:60px;margin-top:20px;">
	<form method="POST" name="formulariouser" id="formulariouser">
		{content_user}
		<p><b>{text_cuenta_email}</b> <input type="text" value="{email}" name="email" id="email" /></p>
		<p>&nbsp;</p>
		<p><b>{text_cuenta_aviso_pass}</b></p>
		<p><b>{text_cuenta_pass_nueva}</b> <input type="password" name="pass1" id="pass1" /></p>
		<p><b>{text_cuenta_pass_confirmar}</b> <input type="password" name="pass2" id="pass2" /></p>
		<p>&nbsp;</p>
		<p><b>{text_cuenta_pass_actual}</b> <input type="password" name="pass_actual" id="pass_actual" /></p>
		
		<br/><input type="hidden" value="edituser" name="save" id="save" />
		<input type="hidden" value="{csrf}" name="csrf" />
		<input style='width:60%;margin-top:32px;' type="submit" class="button" value="{text_cuenta_guardar}" name="button" id="button" />
	</form>
</div>


