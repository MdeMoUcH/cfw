/*** CFW ***/

//Corrige currency.pattern de tablekit.js (fichero de terceros, que no se toca): $, libra, yen, euro y signo de moneda.
if(window.TableKit && TableKit.Sortable && TableKit.Sortable.types.currency){
	TableKit.Sortable.types.currency.pattern = /^[$\u00a3\u00a5\u20ac\u00a4]/;
}
