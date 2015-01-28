var query_1 = prefissi+"SELECT ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo\
(GROUP_CONCAT(DISTINCT ?email ; separator= ' ; ') AS ?c_email)\
				(GROUP_CONCAT(DISTINCT ?numero ; separator= ' ; ') AS ?c_numeri)\
				(GROUP_CONCAT(DISTINCT ?purp_label ; separator= ' ; ') AS ?c_purpose)\
				WHERE{\
					?org a org:Organization;\
							skos:prefLabel ?nome_associazione;\
							foaf:homepage ?link;\
							org:purpose ?purpose.\
					?purpose rdfs:label ?purp_label.\
					OPTIONAL{ ?org org:hasSite ?site.\
									?site a org:Site;\
									org:siteAddress ?site_address.\
									?site_address a vcard:Location.\
									OPTIONAL {?site_address vcard:hasAddress ?address.\
											?address a vcard:Work.\
											OPTIONAL {?address vcard:country-name ?stato;\
																vcard:region ?regione;\
																vcard:postal-code ?cap;\
																vcard:locality ?locality;\
																vcard:street-address ?indirizzo;\
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
				
var elenco_query = [];

elenco_query.push(query_1);



function gestisci_query(){
	$("#bt_query_1").click(function(){
	/*Encoding della query in modo da poterla inviare correttamente*/
    var encodedquery = encodeURIComponent(query_1);
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
	});
	
	
	
	$("#bt_query").click(function(){
		/*Encoding della query in modo da poterla inviare correttamente*/
		var query = crea_query()
		var encodedquery = encodeURIComponent(query);
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
			
			var div_query = "<div id='query_sparql'><pre>"+query+"</pre></div>";

		$("#filtri").find("#query_sparql").remove();
		$("#filtri").append(div_query);
	});
	
	


}




function crea_query(){
	$filtri = [];
	$check_sito = $("#check_sito").prop('checked');
	$check_luogo = $("#check_luogo").prop('checked');
	$check_email = $("#check_email").prop('checked');
	$check_numeri = $("#check_numeri").prop('checked');
	var query = prefissi+"SELECT ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo\
				(GROUP_CONCAT(DISTINCT ?email ; separator= ' ; ') AS ?c_email)\
				(GROUP_CONCAT(DISTINCT ?numero ; separator= ' ; ') AS ?c_numeri)\
				(GROUP_CONCAT(DISTINCT ?purp_label ; separator= ' ; ') AS ?c_purpose)\
				WHERE{\
					?org a org:Organization;\
							skos:prefLabel ?nome_associazione;\
							org:purpose ?purpose.\
					?purpose rdfs:label ?purp_label.";
	if($check_sito == false){
		query += "OPTIONAL{ ?org foaf:homepage ?link}";
	}
	else{
		query += "?org foaf:homepage ?link.";
	}
	if($check_luogo == false && $check_email == false && $check_numeri == false){
		query += "OPTIONAL{ ?org org:hasSite ?site.\
									?site a org:Site;\
									org:siteAddress ?site_address.\
									?site_address a vcard:Location.\
									OPTIONAL {?site_address vcard:hasAddress ?address.\
											?address a vcard:Work.\
											OPTIONAL {?address vcard:country-name ?stato;\
																vcard:region ?regione;\
																vcard:postal-code ?cap;\
																vcard:locality ?locality;\
																vcard:street-address ?indirizzo;\
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
		query +="?org org:hasSite ?site.\
						?site a org:Site;\
						org:siteAddress ?site_address.\
						?site_address a vcard:Location.";
		if($check_luogo == false){
			query +="OPTIONAL {?site_address vcard:hasAddress ?address.\
								?address a vcard:Work.\
								OPTIONAL {?address vcard:country-name ?stato;\
											vcard:region ?regione;\
											vcard:postal-code ?cap;\
											vcard:locality ?locality;\
											vcard:street-address ?indirizzo;\
											}";
		}
		else{
			query +="?site_address vcard:hasAddress ?address.\
								?address a vcard:Work.\
								?address vcard:country-name ?stato;\
											vcard:region ?regione;\
											vcard:postal-code ?cap;\
											vcard:locality ?locality;\
											vcard:street-address ?indirizzo.";
		}
		
		if($check_email == false){
			query +="OPTIONAL {?site_address vcard:hasEmail ?email}";
		}
		else{
			query +="?site_address vcard:hasEmail ?email.";
		}
		
		if($check_numeri == false){
			query +="OPTIONAL {?site_address vcard:hasTelephone ?telephone.\
														?telephone a vcard:Voice;\
														vcard:hasValue ?numero}";
		}
		else{
			query +="?site_address vcard:hasTelephone ?telephone.\
														?telephone a vcard:Voice;\
														vcard:hasValue ?numero.";
		}
	}
	
	query +="}GROUP BY ?nome_associazione ?link ?stato ?regione ?cap ?locality ?indirizzo";

	return query;

}