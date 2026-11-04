<p>{text_cuenta_intro}</p>
<div class="form_cuenta">
	<form method="POST" name="formulariouser" id="formulariouser">
		{content_user}
		<p><b>{text_cuenta_email}</b> <input type="text" value="{email}" name="email" id="email" /></p>
		<p>&nbsp;</p>
		<p><b>{text_cuenta_aviso_pass}</b></p>
		<p><b>{text_cuenta_pass_nueva}</b> <input type="password" data-cifrar="pass1" id="pass1" autocomplete="off" /></p>
		<p><b>{text_cuenta_pass_confirmar}</b> <input type="password" data-cifrar="pass2" id="pass2" autocomplete="off" /></p>
		<p>&nbsp;</p>
		<p><b>{text_cuenta_pass_actual}</b> <input type="password" data-cifrar="pass_actual" id="pass_actual" autocomplete="off" /></p>

		<br/><input type="hidden" value="edituser" name="save" id="save" />
		<input type="hidden" value="{csrf}" name="csrf" />
		<input type="submit" class="button" value="{text_cuenta_guardar}" name="button" id="button" />
	</form>
</div>
