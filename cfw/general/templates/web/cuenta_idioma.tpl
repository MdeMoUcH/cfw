<hr/>
<h2 id="cuenta_idioma_titulo">{text_cuenta_idioma_titulo}</h2>
<form method="POST" name="formularioidioma" id="formularioidioma">
	<fieldset class="form_grupo" aria-labelledby="cuenta_idioma_titulo">{opciones}</fieldset>
	<input type="hidden" value="editidioma" name="save" />
	<input type="hidden" value="{csrf}" name="csrf" />
	<input type="submit" class="button" value="{text_cuenta_idioma_guardar}" name="button_idioma" />
</form>
