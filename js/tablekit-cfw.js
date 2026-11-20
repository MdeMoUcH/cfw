/*** CFW ***/

if(window.TableKit && TableKit.Sortable && TableKit.Sortable.types.currency){
	//Texto de la celda sin espacios a los lados: lo usan la detección del tipo, el orden y el editor en línea.
	var cfw_get_cell_text = TableKit.getCellText;
	TableKit.getCellText = function(cell, refresh){
		var s_texto = cfw_get_cell_text.call(TableKit, cell, refresh);
		return (s_texto == null ? '' : String(s_texto)).trim();
	};

	//Convierte "1.234,50" en 1234.5 sabiendo cuál es el separador decimal; el otro es el de miles.
	var cfw_a_numero = function(s_num, s_decimal){
		var s_miles = s_decimal === ',' ? '.' : ',';
		var f_num = parseFloat(s_num.split(s_miles).join('').split(s_decimal).join('.'));
		return isNaN(f_num) ? 0 : f_num;
	};

	//Con los dos separadores, el último es el decimal; con uno o ninguno, devuelve ''.
	var cfw_decimal_por_orden = function(s_num){
		var i_punto = s_num.lastIndexOf('.');
		var i_coma = s_num.lastIndexOf(',');
		if(i_punto !== -1 && i_coma !== -1){
			return i_punto > i_coma ? '.' : ',';
		}
		return '';
	};

	//Separador decimal del lang de <html>, con Intl; si no se puede saber, el punto.
	var cfw_decimales = {};
	var cfw_decimal_idioma = function(){
		var s_lang = document.documentElement ? document.documentElement.lang : '';
		if(!s_lang || !window.Intl || !Intl.NumberFormat){
			return '.';
		}
		if(!cfw_decimales.hasOwnProperty(s_lang)){
			var s_decimal = '.';
			try{
				var a_partes = new Intl.NumberFormat(s_lang).formatToParts(1.1);
				for(var i = 0; i < a_partes.length; i++){
					if(a_partes[i].type === 'decimal'){
						s_decimal = a_partes[i].value;
					}
				}
			}catch(e){}
			cfw_decimales[s_lang] = s_decimal;
		}
		return cfw_decimales[s_lang];
	};

	//currency de tablekit.js (fichero de terceros, que no se toca): símbolo delante o detrás ($, libra, yen, euro y signo de moneda).
	TableKit.Sortable.types.currency.pattern = /^\s*-?\s*[$\u00a3\u00a5\u20ac\u00a4]|[$\u00a3\u00a5\u20ac\u00a4]\s*$/;
	//Sin mirar el idioma: un único separador es de miles si se repite o le siguen 3 dígitos.
	TableKit.Sortable.types.currency.normal = function(v){
		v = v == null ? '' : String(v);
		var b_negativo = v.indexOf('-') !== -1;
		var s_num = v.replace(/[^\d.,]/g,'');
		var s_decimal = cfw_decimal_por_orden(s_num);
		if(s_decimal === ''){
			var s_sep = s_num.indexOf('.') !== -1 ? '.' : ',';
			var i_pos = s_num.indexOf(s_sep);
			var b_miles = i_pos === -1 || i_pos !== s_num.lastIndexOf(s_sep) || s_num.length - i_pos - 1 === 3;
			s_decimal = b_miles ? (s_sep === '.' ? ',' : '.') : s_sep;
		}
		var f_num = cfw_a_numero(s_num, s_decimal);
		return b_negativo ? -f_num : f_num;
	};

	//number: el primer número del texto; con un único separador, decide el lang de <html>.
	if(TableKit.Sortable.types.number){
		TableKit.Sortable.types.number.normal = function(v){
			var a_num = (v == null ? '' : String(v)).match(/[-+]?(?:\d[\d.,]*|[.,]\d+)(?:[eE][-+]?\d+)?/);
			if(!a_num){
				return 0;
			}
			var s_decimal = cfw_decimal_por_orden(a_num[0]);
			return cfw_a_numero(a_num[0], s_decimal !== '' ? s_decimal : cfw_decimal_idioma());
		};
	}
}
