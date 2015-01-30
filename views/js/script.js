var endpointURL = "http://localhost:3030/noProfit/";

var prefissi = "\prefix skos: <http://www.w3.org/2004/02/skos/core#>\
	prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#>\
	prefix org: <http://www.w3.org/ns/org#>\
	prefix vcard: <http://www.w3.org/2006/vcard/ns#>\
	prefix foaf:  <http://xmlns.com/foaf/0.1/>";

$(document).ready(main);


function main(){
	visualizza_associazioni();
	crea_filtri_cat();
	gestisci_query();
	$("#help_query").popover();
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
				GROUP BY ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo";
			
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
	for(i=0; i<data.length; i++){
		var div = "<div class='div_associazione' id='"+i+"'>";
		var nome = "<div class='nome'><span class='span_title'>Nome: </span>"+data[i].nome_associazione.value+"</div>";
		div += nome;
		if(data[i].hasOwnProperty('link')){
			var link = "<div class='sito'><span class='span_title'>Sito Web: </span><a href='"+data[i].link.value+"' target='_blank'>"+data[i].link.value+"</a></div>";
			div += link;
		}
		if(data[i].hasOwnProperty('c_email')){
			var div_email = "<div class='email'><span class='span_title'>Email: </span><ul>";	
			var email = data[i].c_email.value.split(";");
			for(var j=0;j<email.length;j++){
				div_email += "<li>"+email[j].replace('mailto:','')+"</li>";
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

/*Crea i radio button per la selezione delle categorie*/
function crea_filtri_cat(){

	var query = "SELECT ?categoria ?descrizione WHERE{?categoria a skos:Concept;rdfs:label ?descrizione.}";
	
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
				$id = "check-"+$value.replace(" ","_");
				$check = '<input type="radio" id="'+$id+'" value="'+$value+'" name="radio_cat"/><span>'+$value+'</span>';
				$("#filtri_cat").append("<p>"+$check+"</p>");
			}
			$("#filtri_cat").append('<input type="submit" class="btn btn-default" id="bt_query_cat" value="esegui query"></input>');
			evento_click_bt();
			
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
	
	query +=" }GROUP BY ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo";

	return query;

}

/*Crea dinamicamente la query in basei alle selezioni dell'utente*/

function crea_query_cat(){
	$value = $("input[name='radio_cat']:checked").val();
	var query = "SELECT ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo\
						(GROUP_CONCAT(DISTINCT ?email ; separator= ';') AS ?c_email)\
						(GROUP_CONCAT(DISTINCT ?numero ; separator= ';') AS ?c_numeri)\
						WHERE{\
						?org a org:Organization;\
						skos:prefLabel ?nome_associazione;\
						org:purpose ?purpose.\
						?purpose rdfs:label '"+$value+"'.\
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
						}\
				}\
				GROUP BY ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo ";
	
	return query;

}


	