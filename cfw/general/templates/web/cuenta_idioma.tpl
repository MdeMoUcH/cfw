<hr/>
<h2>{text_cuenta_idioma_titulo}</h2>
<form method="POST" name="formularioidioma" id="formularioidioma">
	{opciones}
	<input type="hidden" value="editidioma" name="save" />
	<input type="hidden" value="{csrf}" name="csrf" />
	<input type="submit" class="button" value="{text_cuenta_idioma_guardar}" name="button_idioma" />
</form>
