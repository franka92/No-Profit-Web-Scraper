var endpointURL = "http://localhost:3030/noProfit/";

var prefissi = "\
	prefix skos: <http://www.w3.org/2004/02/skos/core#>\
	prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#>\
	prefix org: <http://www.w3.org/ns/org#>\
	prefix vcard: <http://www.w3.org/2006/vcard/ns#>\
	prefix foaf:  <http://xmlns.com/foaf/0.1/>";

$(document).ready(main);


function main(){
visualizza_associazioni();
gestisci_query();
crea_filtri_cat();
}

function visualizza_associazioni(){
	var query = prefissi+"SELECT ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo\
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
    var encodedquery = encodeURIComponent(query);
	/*Dichiaro il formato dell'output*/
    var queryUrl = endpointURL + "query?query=" + encodedquery + "&format=" + "json";
	$.ajax({
		method: 'GET',
		url: queryUrl,
		success: function (d){
			visualizza_elenco(d.results.bindings);
			
		},
		error: function (jqXHR, textStatus, errorThrown){
			alert('Errore nel caricamento della lista dei documenti'+errorThrown);
		}
	 
	});

}


function visualizza_elenco(data){
	$("#documento").html("");
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


function crea_filtri_cat(){

	var query = prefissi+"SELECT ?categoria ?descrizione WHERE{?categoria a skos:Concept;rdfs:label ?descrizione.}";
	
	/*Encoding della query in modo da poterla inviare correttamente*/
    var encodedquery = encodeURIComponent(query);
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
				$check = '<input type="checkbox" id="'+$id+'" value="'+$value+'" name="check_cat"/><span>'+$value+'</span>';
				$("#filtri_cat").append("<p>"+$check+"</p>");
			}
			$("#filtri_cat").append('<input type="submit" class="btn btn-default" id="bt_query_cat" value="esegui query"></input>');
			
		},
		error: function (jqXHR, textStatus, errorThrown){
			alert('Errore nel caricamento della lista dei documenti'+errorThrown);
		}
	 
	});
	

	
	
}

	