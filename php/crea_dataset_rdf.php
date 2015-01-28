<?php
	require_once '../vendor/autoload.php';
	require_once '../lib/parsecsv.lib.php';
	
	$graph = new EasyRdf_Graph();
	EasyRdf_Namespace::set('org', 'http://www.w3.org/ns/org#'); 
		
	function crea_data_rdf(){
		global $graph;
		/*Creo il grafo*/
		$elenco_associazioni = recupera_elenco_associazioni();
		inserisci_categorie();
		crea_grafo($elenco_associazioni);
		
		/*Salvo il file*/
		$string = $graph->serialise("turtle");
		$dir_path = '../data/rdf';
		$file = fopen($dir_path."/data_rdf.ttl", "w");
		fwrite($file,utf8_encode($string));
		fclose($file);
	}
	
	
	
	function inserisci_categorie(){
		global $graph;
		$csv_file = new parseCSV();
		$csv_file->auto('../src/categorie.csv');
		$cat = "";
		foreach ($csv_file->data as $key => $row){
			$cur_cat = $row['categoria'];
			if($cur_cat != $cat){
				$parse_cat = str_replace(' ', '_', $cur_cat);
				$categoria = $graph->resource('http://www.no-profit-data.it/cat-'.$parse_cat, 'skos:Concept');
				$categoria->set('rdfs:label',$cur_cat);
			}
		}
		$categoria = $graph->resource('http://www.no-profit-data.it/cat-Associazione_no_profit', 'skos:Concept');
		$categoria->set('rdfs:label',"Associazione no profit");
	}
	
	
	/*Crea il grafo rdf con l'elenco delle associazione e relative informazioni
		@param elenco_associazioni: array con l'elenco/informazioni delle associazioni
	*/
	function crea_grafo($elenco_associazioni){
		global $graph;
		$prefix = "http://www.no-profit-data.it/";
		$elenco_iri = array();
		$count = 0;
		foreach ($elenco_associazioni as $elenco){	
			foreach($elenco as $dati_a){
				$email = null;
				$numeri = null;
				$categorie = null;
				$luogo = null;
				$link = null;
				
				$nome = trim($dati_a['nome']);
				if(array_key_exists('link',$dati_a))
					$link = $dati_a['link'];
				if(array_key_exists('email',$dati_a))
					$email = $dati_a['email'];
				if(array_key_exists('numeri',$dati_a))
					$numeri = $dati_a['numeri'];
				if(array_key_exists('categoria',$dati_a))
					$categorie = $dati_a['categoria'];
				if(array_key_exists('luogo',$dati_a))
					$luogo = $dati_a['luogo'];
				
				$count_location = 1;
				$count_site = 1;
				
				/*Creo l'identificatore per l'associazione*/
				if($link != null){
					$parse = parse_url($link);
					$parse_link = $parse['host'];
					$dot = strrpos($parse_link,'.');
					if(strpos($parse_link,"www") !== false)
						$parse_link = substr($parse_link,4,count($parse_link)-4);
					else
						$parse_link = substr($parse_link,0,count($parse_link)-4);
					
					$parse_link = rtrim($parse_link,".");
					$parse_link = trim($parse_link,".");
					
				}
				else{
					$parse_link = str_replace(" ","_",$nome);
				}
				$iri_associazione = $prefix.$parse_link;
				$count_iri = count(array_keys($elenco_iri,$iri_associazione));
				if($count_iri >0 )
					$iri_associazione .="_".$count_iri;
				array_push($elenco_iri,$iri_associazione);
				/*Creo l'oggetto Associazione*/
				$associazione = $graph->resource($iri_associazione, 'org:Organization');
				$associazione->set("skos:prefLabel",$nome);
				$associazione->set("foaf:homepage",$link);
				
				/*Assicio le categorie all'associazione*/
				if(count($categorie) >0){
					foreach($categorie as $cat){
						
						$associazione->addResource("org:purpose","http://www.no-profit-data.it/cat-".str_replace(" ","_",$cat));
					}
				}

				
				
				/*Creo l'oggetto Site*/
				if($email != null || $numeri != null || $luogo != null){
					$iri_site = $prefix.'site-'.$parse_link.'_'.$count_site;
					$site = $graph->resource($iri_site, 'org:Site');
					
					/*Creo l'oggetto Location*/
					$iri_location = $prefix.'loc-'.$parse_link.'_'.$count_location;
					$location = $graph->resource($iri_location, 'vcard:Location');
					
					/*Imposto i predicati/proprietà per ogni oggetto*/
					if($email != null){
						foreach($email as $e){
							$e_obj = $graph->resource("mailto:".$e);
							$location->add('vcard:hasEmail',$e_obj);
						}
							
					}
					if($numeri != null){
						foreach($numeri as $n){
							$num_type = $n[1];
							$numero = $graph->resource("tel:".$n[0]);
							$telefono = $graph->resource($prefix."number-".$n[0],"vcard:Voice");
							$telefono->add('vcard:hasValue',$numero);
							$location->addResource("vcard:hasTelephone",$telefono);
						}
							
					}
					if($luogo != null){
						$indirizzo = null;
						if(array_key_exists("indirizzo",$luogo) === true){
							$indirizzo = $luogo['indirizzo'];
							$indirizzo = str_replace("\\","-",$indirizzo);
							$iri_address = $prefix.str_replace(" ","_",$indirizzo)."_".$luogo['cap'];
						}
						else{
							$iri_address = $prefix.$luogo['cap'];
						}
						$address = $graph->resource($iri_address,"vcard:Work");
						$address->set("vcard:country-name","Italia");
						$address->set("vcard:region",$luogo['regione']);
						$address->set("vcard:postal-code",$luogo['cap']);
						$address->set("vcard:locality",$luogo['comune']);
						if($indirizzo != null)
							$address->set("vcard:street-address",$luogo['indirizzo']);
						$location->addResource("vcard:hasAddress",$address);
					}
					
					/*Collego il Site alla Location*/
					$site->addResource("org:siteAddress",$location);
					
					/*Collego l'Organization al Site*/
					$associazione->addResource("org:hasSite",$site);
					
				}	
			}
		}
		
	}
	
	/*Parsing della stringa per rimuovere gli spazi
		@param stringa: stringa da modificare	
		@return: stringa modificata
	*/
	function parse_string($stringa){
		$parse_cat = str_replace(' ', '_', $stringa);
		$parse_cat = str_replace('à', 'a', $stringa);
		$unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
		$parse_cat = strtr( $parse_cat, $unwanted_array );
		return $parse_cat;
	}
	
	
	
	/*Funzione che recupera l'elenco delle associazioni dai file .csv
		@return: array con l'elenco delle associazioni e relative informazioni
	*/
	
	function recupera_elenco_associazioni(){
		$elenco = array();
		$count;
		$file_content = file_get_contents("../data/json/associazioni.json");
		$file_content_two = file_get_contents("../data/json/associazioni_no_sito.json");
		$data_json = json_decode($file_content,true);
		$data_json_two = json_decode($file_content_two,true);
		array_push($elenco,$data_json);
		array_push($elenco,$data_json_two);
		return $elenco;

	}

?>
