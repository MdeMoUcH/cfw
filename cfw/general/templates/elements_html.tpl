<div>
	<h2>{titulo}</h2>
	<hr/>
	<div id="mensaje">{mensaje}&nbsp;</div>
	<p>{content}</p>
	<br/>
	<hr/>
	<a href="{urlbase}admin/">admin</a>&nbsp;|&nbsp;<a href="{urlbase}admin/{slug}">lista</a>&nbsp;|&nbsp;<a href="{urlbase}admin/{slug}/form">nuevo</a>
</div>

<script type="text/javascript">
	$(function(){
		$(".campo_html").htmlarea();
		$("table").css("width","100%");
		$(".jHtmlArea").css("width","100%");
		$(".ToolBar").css("width","100%");
		$(".jHtmlArea iframe").css("width","100%");
		$(".jHtmlArea iframe").css("height","420px");
		$(".jHtmlArea iframe").css("margin-top","35px");
	});
</script>
