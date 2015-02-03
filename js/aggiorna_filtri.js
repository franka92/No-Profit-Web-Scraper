/*Questo script si occupa di  caricare le informazioni relative alle regioni e le relative province.
  Gestisce le selezioni da parte del'utente

*/
$(document).ready(main);

/*Funzione principale*/
function main(){
	caricaRegioni();
}

/*	Funzione che crea dinamicamente le checkbox relative alle regioni.
	Effettua una chiamata Ajax al file carica_regioni.php che restituisce l'elenco delle regioni sottoforma di oggetto di tipo JSON
*/
function caricaRegioni(){
	$.ajax({
		url: "php/carica_regioni.php",
		type: "POST",
		dataType: "json",
		success: function(data){
			for(var i=0;i<data.length;i++){
				if(data[i].codice != ""){
					$("#elenco_regioni").append("<div id='reg_"+data[i].codice+"' class='div_regione'><input type='checkbox' name='regioni[]' value='"+data[i].nome+"_"+data[i].codice+"' data-id='"+data[i].codice+"' id='r_"+data[i].codice+"'></input> "+data[i].nome+"<br>");
					/*Gestione dell'evento "onClick" associato alla checkbox creata*/
					$("#r_"+data[i].codice).click(function(){
						caricaProvince($(this).attr("data-id"));
					});
				}
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			alert("Errore "+textStatus);
		}
	});

}

/*	Funzione che crea dinamicamente le checkbox delle province, relative alla regione selezionata dall'utente.
	Effettua una chiamata Ajax al file carica_province.php che restituisce l'elenco delle province sottoforma di oggetto di tipo JSON
	
	@param cod_reg: codice della regione di cui si vogliono recuperare le province
*/
function caricaProvince(cod_reg){
	if($("#r_"+cod_reg).is(':checked')){
		$.ajax({
			url: "php/carica_province.php?cod_reg="+cod_reg,
			type: "POST",
			dataType: "json",
			success: function(data){
				for(var i=0;i<data.length;i++){
					if(data[i].codice != ""){
					$("#reg_"+cod_reg).append("<p><input type='checkbox' class='"+cod_reg+"' name='province[]' value='"+data[i].nome+"_"+cod_reg+"' data-id='"+data[i].codice+"' id='p_"+data[i].codice+"'></input> <label class='"+cod_reg+"'>"+data[i].nome+"</label></p>");
					}
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert("Errore "+textStatus);
			}
		});
	}
	else{
		$("#reg_"+cod_reg).find("p").remove();
		/*$("#elenco_province").find("input."+cod_reg).remove();
		$("#elenco_province").find("label."+cod_reg).remove();*/
	}

}