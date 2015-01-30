var endpointURL = "http://localhost:3030/noProfit/";

var prefissi = "\prefix skos: <http://www.w3.org/2004/02/skos/core#>\
	prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#>\
	prefix org: <http://www.w3.org/ns/org#>\
	prefix vcard: <http://www.w3.org/2006/vcard/ns#>\
	prefix foaf:  <http://xmlns.com/foaf/0.1/>";
	
var categorie = [];

$(document).ready(main);


function main(){
	visualizza_associazioni();
	crea_filtri_cat();
	gestisci_query();
	recupera_elenco_comuni();
	$("#help_query").popover();	
	
	
	$("#check_sel_tutto").change(function() {
        if($(this).is(":checked")) {
            $("input[name='check_query']").each(function(){
				$(this).prop('checked', 'true');
			});
			if($("#check_des_tutto").is(":checked"))
				$("#check_des_tutto").trigger('click');
        }
    });
	
	$("#check_des_tutto").change(function() {
        if($(this).is(":checked")) {
            $("input[name='check_query']").each(function(){
				$(this).removeAttr('checked');
			});
			if($("#check_sel_tutto").is(":checked"))
				$("#check_sel_tutto").trigger('click');
        }
    });
	
	$('#bt_ricerca').click(function(){
		var input = $("#text_ricerca").val();
		cerca_associazione(input);
	
	});
	
	$("#bt_query_comuni").click(function(){
		/*Encoding della query in modo da poterla inviare correttamente*/
		var query = crea_query_comuni();
		if(query != 0){
			var encodedquery = encodeURIComponent(prefissi+query);
			/*Dichiaro il formato dell'output*/
			var url = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
				$.ajax({
					method: 'GET',
					url: url,
					success: function (d){
						visualizza_elenco(d.results.bindings);
						
					},
					error: function (jqXHR, textStatus, errorThrown){
						alert('Errore nel caricamento della lista dei documenti'+errorThrown);
					}
				 
				});
			visualizza_query(query);
		}
		else{
			alert("Devi selezionare una o più categorie");
		}
	});
	
}

function cerca_associazione(filtro){
	var query = "SELECT ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo\
				(GROUP_CONCAT(DISTINCT ?email ; separator= ' ; ') AS ?c_email)\
				(GROUP_CONCAT(DISTINCT ?numero ; separator= ' ; ') AS ?c_numeri)\
				(GROUP_CONCAT(DISTINCT ?purp_label ; separator= ' ; ') AS ?c_purpose)\
				WHERE{\
					?org a org:Organization;\
							skos:prefLabel ?nome_associazione;\
							org:purpose ?purpose.\
					?purpose rdfs:label ?purp_label.\
					OPTIONAL{ ?org foaf:homepage ?link}\
					OPTIONAL{ ?org org:hasSite ?site.\
									?site a org:Site;\
									org:siteAddress ?site_address.\
									?site_address a vcard:Location.\
									OPTIONAL {?site_address vcard:hasAddress ?address.\
											?address a vcard:Work.\
											OPTIONAL {?address vcard:country-name ?stato;\
																vcard:region ?regione;\
																vcard:locality ?locality.\
																OPTIONAL{?address vcard:postal-code ?cap}\
																OPTIONAL{?address vcard:street-address ?indirizzo.}\
											}\
											OPTIONAL {?site_address vcard:hasEmail ?email}\
											OPTIONAL {?site_address vcard:hasTelephone ?telephone.\
														?telephone a vcard:Voice;\
														vcard:hasValue ?numero\
											}\
									}\
					}\
					FILTER regex(?nome_associazione, '"+filtro+"', 'i')\
				}\
				GROUP BY ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo ORDER BY ?nome_associazione";
				
				
		/*Encoding della query in modo da poterla inviare correttamente*/
		var encodedquery = encodeURIComponent(prefissi+query);
		/*Dichiaro il formato dell'output*/
		var queryUrl = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
		$.ajax({
			method: 'GET',
			url: queryUrl,
			success: function (d){
				visualizza_elenco(d.results.bindings);
				$("#tab_risultati").find('span.filtri').remove();
				$("#tab_risultati").prepend("<span class='filtri'>Hai cercato: <span class='filtro_ricerca'>"+filtro+"</span> - </span>");
				visualizza_query(query);
				
			},
			error: function (jqXHR, textStatus, errorThrown){
				alert('Errore nel caricamento della lista dei documenti'+errorThrown);
			}
		 
		});
}

/*Funzione che recupera l'elenco totale delle associazioni*/
function visualizza_associazioni(){
	var query = "SELECT ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo\
				(GROUP_CONCAT(DISTINCT ?email ; separator= ' ; ') AS ?c_email)\
				(GROUP_CONCAT(DISTINCT ?numero ; separator= ' ; ') AS ?c_numeri)\
				(GROUP_CONCAT(DISTINCT ?purp_label ; separator= ' ; ') AS ?c_purpose)\
				WHERE{\
					?org a org:Organization;\
							skos:prefLabel ?nome_associazione;\
							org:purpose ?purpose.\
					?purpose rdfs:label ?purp_label.\
					OPTIONAL{ ?org foaf:homepage ?link}\
					OPTIONAL{ ?org org:hasSite ?site.\
									?site a org:Site;\
									org:siteAddress ?site_address.\
									?site_address a vcard:Location.\
									OPTIONAL {?site_address vcard:hasAddress ?address.\
											?address a vcard:Work.\
											OPTIONAL {?address vcard:country-name ?stato;\
																vcard:region ?regione;\
																vcard:locality ?locality.\
																OPTIONAL{?address vcard:postal-code ?cap}\
																OPTIONAL{?address vcard:street-address ?indirizzo.}\
											}\
											OPTIONAL {?site_address vcard:hasEmail ?email}\
											OPTIONAL {?site_address vcard:hasTelephone ?telephone.\
														?telephone a vcard:Voice;\
														vcard:hasValue ?numero\
											}\
									}\
					}\
				}\
				GROUP BY ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo ORDER BY ?nome_associazione";
			
	/*Encoding della query in modo da poterla inviare correttamente*/
    var encodedquery = encodeURIComponent(prefissi+query);
	/*Dichiaro il formato dell'output*/
    var queryUrl = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
	$.ajax({
		method: 'GET',
		url: queryUrl,
		success: function (d){
			visualizza_elenco(d.results.bindings);
			visualizza_query(query);
			
		},
		error: function (jqXHR, textStatus, errorThrown){
			alert('Errore nel caricamento della lista dei documenti'+errorThrown);
		}
	 
	});

}

function visualizza_query(query){

	$prefix = prefissi.replace(/</g,'&lt;');
	var div_query = "<div id='query_sparql'><pre><code>"+$prefix+"	"+query+"</code></pre></div>";

	$("#div_query").find("#query_sparql").remove();
	$("#div_query").append(div_query);
	$('pre').html(function() {
		return this.innerHTML.replace(/\t{1,}/g, '<br>');
	});
}

/*Visualizza l'elenco delle associazioni e i relativi dati
	@param data: oggetto json contenente i vari dati
*/
function visualizza_elenco(data){
	$("#div_elenco").html("");
	$("#tab_risultati").find('span.filtri').remove();
	if(data.length > 1){
		for(i=0; i<data.length; i++){
			var div = "<div class='div_associazione' id='"+i+"'>";
			var nome = "<div class='nome'><span class='title_nome'>"+data[i].nome_associazione.value+"</span></div>";
			div += nome;
			if(data[i].hasOwnProperty('link')){
				var link = "<div class='sito'><span class='span_title'>Sito Web: </span><a href='"+data[i].link.value+"' target='_blank'>"+data[i].link.value+"</a></div>";
				div += link;
			}
			if(data[i].hasOwnProperty('c_email')){
				var div_email = "<div class='email'><span class='span_title'>Email: </span><ul>";	
				var email = data[i].c_email.value.split(";");
				for(var j=0;j<email.length;j++){
					div_email += "<li><a href='"+email[j]+"'>"+email[j].replace('mailto:','')+"</a></li>";
				}
				div_email += "</ul></div>";
				div += div_email;
			}
			if(data[i].hasOwnProperty('c_numeri')){
				var div_numeri = "<div class='numeri'><span class='span_title'>Contatti telefonici: </span><ul>";	
				var numeri = data[i].c_numeri.value.split(";");
				for(var j=0;j<numeri.length;j++){
					div_numeri += "<li>"+numeri[j].replace('tel:','')+"</li>";
				}
				div_numeri += "</ul></div>";
				div += div_numeri;
			}
			if(data[i].hasOwnProperty('stato')){
				var div_luogo = "<div class='luogo'><span class='span_title'>Luogo: </span><ul>";
					if(data[i].hasOwnProperty('indirizzo'))
						div_luogo += "<li>Indirizzo: "+data[i].indirizzo.value+"</li>";
					if(data[i].hasOwnProperty('locality'))
						div_luogo += "<li>Comune: "+data[i].locality.value+"</li>";
					if(data[i].hasOwnProperty('cap'))
						div_luogo += "<li>Cap: "+data[i].cap.value+"</li>";
					if(data[i].hasOwnProperty('regione'))
						div_luogo += "<li>Regione (sigla): "+data[i].regione.value+"</li>";
					div_luogo += "<li>Stato: "+data[i].stato.value+"</li>";
				div_luogo += "</ul></div>";
				div += div_luogo;
			}
			if(data[i].hasOwnProperty('c_purpose')){
				var div_category = "<div class='purpose'><span class='span_title'>Categoria: </span><ul>";	
				var category = data[i].c_purpose.value.split(";");
				for(var j=0;j<category.length;j++){
					div_category += "<li>"+category[j]+"</li>";
				}
				div_category += "</ul></div>";
				div += div_category;
			}
			div +="</div>";
			$("#div_elenco").append(div);
		}
		$("#tot_res").html(data.length);
	}
	else{
		$("#tot_res").html("0");
	}
	
	

}

/*Crea i radio button per la selezione delle categorie*/
function crea_filtri_cat(){

	var query = "SELECT ?categoria ?descrizione WHERE{?categoria a skos:Concept;rdfs:label ?descrizione.} ORDER BY ?descrizione";
	
	/*Encoding della query in modo da poterla inviare correttamente*/
    var encodedquery = encodeURIComponent(prefissi+query);
	/*Dichiaro il formato dell'output*/
    var queryUrl = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
	$.ajax({
		method: 'GET',
		url: queryUrl,
		success: function (d){
			
			$dati = d.results.bindings;
			for(var i=0;i<$dati.length;i++){
				$value =$dati[i].descrizione.value;
				categorie.push($value);
				if($value != "Associazione no profit"){
					$id = "check-"+$value.replace(" ","_");
					$check = '<input type="checkbox" class="check_cat" id="'+$id+'" value="'+$value+'" name="check_cat"/><span>'+$value+'</span>';
					$("#filtri_cat").append("<p>"+$check+"</p>");
				}
			}
			evento_click_bt();
			crea_grafico_categorie();
			crea_grafico_dati();
			
		},
		error: function (jqXHR, textStatus, errorThrown){
			alert('Errore nel caricamento della lista dei documenti'+errorThrown);
		}
	 
	});
	
	

	
	
}
/*Associa l'evento on-click sul pulsante di esecuzione della query (per categorie)*/
function evento_click_bt(){

	$("#bt_query_cat").click(function(){
		/*Encoding della query in modo da poterla inviare correttamente*/
		var query = crea_query_cat()
		if(query != 0){
			var encodedquery = encodeURIComponent(prefissi+query);
			/*Dichiaro il formato dell'output*/
			var url = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
				$.ajax({
					method: 'GET',
					url: url,
					success: function (d){
						visualizza_elenco(d.results.bindings);
						
					},
					error: function (jqXHR, textStatus, errorThrown){
						alert('Errore nel caricamento della lista dei documenti'+errorThrown);
					}
				 
				});
			visualizza_query(query);
		}
		else{
			alert("Devi selezionare una o più categorie");
		}
	});


}

/*Associa l'evento on-click sul pulsante di esecuzione della query (per campi obbligatori)*/
function gestisci_query(){
	
	$("#bt_query").click(function(){
		/*Encoding della query in modo da poterla inviare correttamente*/
		var query = crea_query()
		var encodedquery = encodeURIComponent(prefissi+query);
		/*Dichiaro il formato dell'output*/
		var url = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
			$.ajax({
				method: 'GET',
				url: url,
				success: function (d){
					visualizza_elenco(d.results.bindings);
					
				},
				error: function (jqXHR, textStatus, errorThrown){
					alert('Errore nel caricamento della lista dei documenti'+errorThrown);
				}
			 
			});
			visualizza_query(query);
	});
	

}

/*Crea dinamicamente la query in basei alle selezioni dell'utente*/
function crea_query(){
	$filtri = [];
	$check_sito = $("#check_sito").prop('checked');
	$check_luogo = $("#check_luogo").prop('checked');
	$check_email = $("#check_email").prop('checked');
	$check_numeri = $("#check_numeri").prop('checked');
	var query = "SELECT ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo\
				(GROUP_CONCAT(DISTINCT ?email ; separator= ' ; ') AS ?c_email)\
				(GROUP_CONCAT(DISTINCT ?numero ; separator= ' ; ') AS ?c_numeri)\
				(GROUP_CONCAT(DISTINCT ?purp_label ; separator= ' ; ') AS ?c_purpose)\
				WHERE{\
					?org a org:Organization;\
							skos:prefLabel ?nome_associazione;\
							org:purpose ?purpose.\
					?purpose rdfs:label ?purp_label.";
	if($check_sito == false){
		query += " OPTIONAL{ ?org foaf:homepage ?link}";
	}
	else{
		query += " ?org foaf:homepage ?link.";
	}
	if($check_luogo == false && $check_email == false && $check_numeri == false){
		query += " OPTIONAL{ ?org org:hasSite ?site.\
									?site a org:Site;\
									org:siteAddress ?site_address.\
									?site_address a vcard:Location.\
									OPTIONAL {?site_address vcard:hasAddress ?address.\
											?address a vcard:Work.\
											OPTIONAL {?address vcard:country-name ?stato;\
																vcard:region ?regione;\
																vcard:postal-code ?cap;\
																vcard:locality ?locality.\
														OPTIONAL{?address vcard:street-address ?indirizzo}\
											}\
											OPTIONAL {?site_address vcard:hasEmail ?email}\
											OPTIONAL {?site_address vcard:hasTelephone ?telephone.\
														?telephone a vcard:Voice;\
														vcard:hasValue ?numero\
											}\
									}\
						}";
	}
	else {
		query +=" ?org org:hasSite ?site.\
						?site a org:Site;\
						org:siteAddress ?site_address.\
						?site_address a vcard:Location. ";
		if($check_luogo == false){
			query +=" OPTIONAL {?site_address vcard:hasAddress ?address.\
								?address a vcard:Work.\
								?address vcard:country-name ?stato;\
											vcard:region ?regione;\
											vcard:postal-code ?cap;\
											vcard:locality ?locality. \
											OPTIONAL{?address vcard:street-address ?indirizzo.}\
											}";
		}
		else{
			query +=" ?site_address vcard:hasAddress ?address.\
								?address a vcard:Work.\
								?address vcard:country-name ?stato;\
											vcard:region ?regione;\
											vcard:postal-code ?cap;\
											vcard:locality ?locality.\
											OPTIONAL{?address vcard:street-address ?indirizzo}";
		}
		
		if($check_email == false){
			query +=" OPTIONAL {?site_address vcard:hasEmail ?email}";
		}
		else{
			query +=" ?site_address vcard:hasEmail ?email.";
		}
		
		if($check_numeri == false){
			query +=" OPTIONAL {?site_address vcard:hasTelephone ?telephone.\
														?telephone a vcard:Voice;\
														vcard:hasValue ?numero}";
		}
		else{
			query +=" ?site_address vcard:hasTelephone ?telephone.\
														?telephone a vcard:Voice;\
														vcard:hasValue ?numero.";
		}
	}
	
	query +=" }GROUP BY ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo ORDER BY ?nome_associazione";

	return query;

}

/*Crea dinamicamente la query in basei alle selezioni dell'utente*/

function crea_query_cat(){
	var filtro = "";

	 $("input[name='check_cat']").each(function(){
		var checked = $(this).prop('checked');
		if(checked == true){
			if(filtro == "")
				filtro = "FILTER ( ?purp_label = '"+$(this).val()+"'";
			else
				filtro +=" || ?purp_label = '"+$(this).val()+"'";
		}
	});
	if(filtro != ""){
		filtro += ")";
		$value = $("input[name='radio_cat']:checked").val();
		var query = "SELECT ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo\
							(GROUP_CONCAT(DISTINCT ?email ; separator= ';') AS ?c_email)\
							(GROUP_CONCAT(DISTINCT ?numero ; separator= ';') AS ?c_numeri)\
							(GROUP_CONCAT(DISTINCT ?purp_label ; separator= ' ; ') AS ?c_purpose)\
							WHERE{\
							?org a org:Organization;\
							skos:prefLabel ?nome_associazione;\
							org:purpose ?purpose.\
							?purpose rdfs:label ?purp_label.\
							OPTIONAL{ ?org foaf:homepage ?link}\
							OPTIONAL{ ?org org:hasSite ?site.\
									  ?site a org:Site;\
									  org:siteAddress ?site_address.\
									  ?site_address a vcard:Location.\
									  OPTIONAL {?site_address vcard:hasAddress ?address.\
													?address a vcard:Work.\
													OPTIONAL {?address vcard:country-name ?stato;\
														   vcard:region ?regione;\
														   vcard:postal-code ?cap;\
														   vcard:locality ?locality.\
														   OPTIONAL{?address vcard:street-address ?indirizzo.}\
													}\
													OPTIONAL {?site_address vcard:hasEmail ?email. }\
													OPTIONAL {?site_address vcard:hasTelephone ?telephone.\
															  ?telephone a vcard:Voice;\
															  vcard:hasValue ?numero\
													}\
										}\
							}	"+ filtro +"\
					}\
					GROUP BY ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo ORDER BY ?nome_associazione";
		
		return query;
	}
	else{
		return 0;
	}

}


function crea_query_comuni(){
	var filtro = "";

	 $("input[name='check_com']").each(function(){
		var checked = $(this).prop('checked');
		if(checked == true){
			if(filtro == "")
				filtro = "FILTER ( ?locality = '"+$(this).val()+"'";
			else
				filtro +=" || ?locality = '"+$(this).val()+"'";
		}
	});
	if(filtro != ""){
		filtro += ")";
		$value = $("input[name='radio_cat']:checked").val();
		var query = "SELECT ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo\
							(GROUP_CONCAT(DISTINCT ?email ; separator= ';') AS ?c_email)\
							(GROUP_CONCAT(DISTINCT ?numero ; separator= ';') AS ?c_numeri)\
							(GROUP_CONCAT(DISTINCT ?purp_label ; separator= ' ; ') AS ?c_purpose)\
							WHERE{\
							?org a org:Organization;\
							skos:prefLabel ?nome_associazione;\
							org:purpose ?purpose.\
							?purpose rdfs:label ?purp_label.\
							OPTIONAL{ ?org foaf:homepage ?link}\
							?org org:hasSite ?site.\
									  ?site a org:Site;\
									  org:siteAddress ?site_address.\
									  ?site_address a vcard:Location.\
									  ?site_address vcard:hasAddress ?address.\
											?address a vcard:Work.\
											?address vcard:country-name ?stato;\
												vcard:region ?regione;\
												vcard:postal-code ?cap;\
												vcard:locality ?locality.\
												OPTIONAL{?address vcard:street-address ?indirizzo.}\
										OPTIONAL {?site_address vcard:hasEmail ?email. }\
										OPTIONAL {?site_address vcard:hasTelephone ?telephone.\
													?telephone a vcard:Voice;\
													vcard:hasValue ?numero\
												}\
								"+ filtro +"\
					}\
					GROUP BY ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo ORDER BY ?nome_associazione";
		
		return query;
	}
	else{
		return 0;
	}

}


function crea_grafico_categorie(){
	var count = [];

	var query='SELECT (count (?org) as ?count) ?label WHERE{\
					?org a org:Organization;\
					org:purpose ?purp.\
					?purp a skos:Concept;\
					rdfs:label ?label.\
					} GROUP BY ?label';
		var encodedquery = encodeURIComponent(prefissi+query);
		/*Dichiaro il formato dell'output*/
		var url = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
			$.ajax({
				method: 'GET',
				url: url,
				success: function (d){
					$data = d.results.bindings;
					for(var i=0;i<$data.length;i++){
						if($data[i].label.value != "Associazione no profit")
							count.push({y: Number($data[i].count.value), indexLabel: $data[i].label.value});
					}	
					
					var chart = new CanvasJS.Chart("chartCategorie",{
					theme: "theme1",
							title :{
								text: "Grafico Categorie"
							},
							legend:{
								verticalAlign: "bottom",
								horizontalAlign: "center"
							},
							data: [{
								type: "pie",
								dataPoints : count
							}]
						});

						chart.render();
									
				},
				error: function (jqXHR, textStatus, errorThrown){
					alert('Errore nel caricamento della lista dei documenti'+errorThrown);
				}
			 
			});



}

function crea_grafico_dati(){
	var tot_siti = count_siti();
	var tot_luogo = count_luogo();
	var tot_email = count_email();
	var tot_telefono = count_telefono();
	
	var chart = new CanvasJS.Chart("chartDati",{
							title :{
								text: "Grafico Dati Raccolti"
							},
							data: [{
								type: "stackedColumn",
								 dataPoints: [
								{ y: Number(tot_siti), label: "Sito Web"},
								{ y: Number(tot_luogo),  label: "Luogo" },
								{ y: Number(tot_email), label: "Email"},
								{ y: Number(tot_telefono),  label: "Telefono" },

								]
							}]
						});

						chart.render();


}

function count_siti(){
	var query='SELECT (count (DISTINCT ?org) as ?count) WHERE{\
					?org a org:Organization;\
					foaf:homepage ?link.}';
	var encodedquery = encodeURIComponent(prefissi+query);
	var count;
	/*Dichiaro il formato dell'output*/
	var url = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
		$.ajax({
			method: 'GET',
			url: url,
			 async: false,
			success: function (d){
				$data = d.results.bindings;
				count = $data[0].count.value;
								
			},
			error: function (jqXHR, textStatus, errorThrown){
				alert('Errore nel caricamento della lista dei documenti'+errorThrown);
			}
		 
		});
		
		return count;
}
function count_luogo(){
	var query="SELECT (count (DISTINCT ?org) as ?count) WHERE{\
				?org a org:Organization;\
				org:hasSite ?site.\
				?site a org:Site;\
				org:siteAddress ?site_address.\
				?site_address vcard:hasAddress ?address.}";
	var count;
	var encodedquery = encodeURIComponent(prefissi+query);
	/*Dichiaro il formato dell'output*/
	var url = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
		$.ajax({
			method: 'GET',
			url: url,
			 async: false,
			success: function (d){
				$data = d.results.bindings;
				count = ($data[0].count.value);
								
			},
			error: function (jqXHR, textStatus, errorThrown){
				alert('Errore nel caricamento della lista dei documenti'+errorThrown);
			}
		 
		});
		return count;
}

function count_email(){
	var query="SELECT (count (DISTINCT ?org) as ?count) WHERE{\
				?org a org:Organization;\
				org:hasSite ?site.\
				?site a org:Site;\
				org:siteAddress ?site_address.\
				?site_address vcard:hasEmail ?email.}";
	var count;
	var encodedquery = encodeURIComponent(prefissi+query);
	/*Dichiaro il formato dell'output*/
	var url = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
		$.ajax({
			method: 'GET',
			url: url,
			 async: false,
			success: function (d){
				$data = d.results.bindings;
				count = ($data[0].count.value);
								
			},
			error: function (jqXHR, textStatus, errorThrown){
				alert('Errore nel caricamento della lista dei documenti'+errorThrown);
			}
		 
		});
		return count;
}

function count_telefono(){
	var query="SELECT (count (DISTINCT ?org) as ?count) WHERE{\
				?org a org:Organization;\
				org:hasSite ?site.\
				?site a org:Site;\
				org:siteAddress ?site_address.\
				?site_address vcard:hasTelephone ?telephone.}";
	var count;
	var encodedquery = encodeURIComponent(prefissi+query);
	/*Dichiaro il formato dell'output*/
	var url = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
		$.ajax({
			method: 'GET',
			url: url,
			 async: false,
			success: function (d){
				$data = d.results.bindings;
				count = ($data[0].count.value);
								
			},
			error: function (jqXHR, textStatus, errorThrown){
				alert('Errore nel caricamento della lista dei documenti'+errorThrown);
			}
		 
		});
		return count;
}

function recupera_elenco_comuni(){
	$.ajax({
        type: "GET",
        url: "./src/listacomuni.csv",
        dataType: "text",
        success: function(data) {crea_check_comuni(data);}
     });

}

function crea_check_comuni(allText) {
    var allTextLines = allText.split(/\r\n|\n/);
    var headers = allTextLines[0].split(';');
    var comuni = [];
    for (var i=1; i<allTextLines.length; i++) {
        var data = allTextLines[i].split(';');
        if (data.length == headers.length) {
            for (var j=0; j<headers.length; j++) {
				if(headers[j] == "Provincia" && data[j] == "BO"){
					console.log(data[j-1]);
					//$c =$data[j-1]
					$id="check-"+data[j-1].replace(" ","_");
					$check = '<input type="checkbox" class="check_cat" id="'+$id+'" value="'+data[j-1].replace("'","\\'")+'" name="check_com"/><span>'+data[j-1]+'</span>';
					$("#filtri_comune").append("<p>"+$check+"</p>");
				}

            }
        }
    }	
	

	
	
}

	