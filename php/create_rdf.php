<?php
	require_once '../vendor/autoload.php';
	require_once '../lib/parsecsv.lib.php';
	include ("../php/database_manager.php");

?>

<html>
<head>
  <title>Basic FOAF example</title>
</head>
<body>

<?php
		
	$graph = new EasyRdf_Graph();
	EasyRdf_Namespace::set('org', 'http://www.w3.org/ns/org#'); 
		
	
	/*Creo il grafo*/
	$elenco_associazioni = recupera_elenco_associazioni();
	inserisci_categorie();
	crea_grafo($elenco_associazioni);
	
	/*Salvo il file*/
	$string = $graph->serialise("turtle");
	$file = fopen("grafo.ttl", "w");
	fwrite($file,utf8_encode($string));
	fclose($file);
	
	
	function inserisci_categorie(){
		global $graph;
		$csv_file = new parseCSV();
		$csv_file->auto('../src/categorie.csv');
		$cat = "";
		foreach ($csv_file->data as $key => $row){
			$cur_cat = $row['categoria'];
			if($cur_cat != $cat){
				$parse_cat = str_replace(' ', '_', $cur_cat);
				$categoria = $graph->resource('http://www.no-profit-data.it/res/categorie/'.$parse_cat, 'skos:Concept');
				$categoria->set('rdfs:label',$cur_cat);
			}
		}
	}
	
	
	/*Crea il grafo rdf con l'elenco delle associazione e relative informazioni
		@param elenco_associazioni: array con l'elenco/informazioni delle associazioni
	*/
	function crea_grafo($elenco_associazioni){
		global $graph;
		$prefix = "http://www.no-profit-data.it/res/";
		
		foreach($elenco_associazioni as $dati_a){
			$link = $dati_a['sito'];
			$nome = trim($dati_a['nome']);
			$email = $dati_a['email'];
			$numeri = $dati_a['numeri'];
			$categorie = $dati_a['categorie'];
			$luogo = $dati_a['luogo'];
			
			$count_location = 1;
			$count_site = 1;
			
			/*Creo l'identificatore per l'associazione*/
			$parse = parse_url($link);
			$parse_link = $parse['host'];
			$dot = strrpos($parse_link,'.');
			if(strpos($parse_link,"www") !== false)
				$parse_link = substr($parse_link,4,count($parse_link)-4);
			else
				$parse_link = substr($parse_link,0,count($parse_link)-4);
			$iri_associazione = $prefix.$parse_link;
			
			/*Creo l'oggetto Associazione*/
			$associazione = $graph->resource($iri_associazione, 'org:Organization');
			$associazione->set("skos:prefLabel",$nome);
			
			/*Assicio le categorie all'associazione*/
			if(count($categorie) >0){
				foreach($categorie as $cat){
					
					$associazione->addResource("org:purpose","http://www.no-profit-data.it/res/categorie/".$cat);
				}
			}

			
			
			/*Creo l'oggetto Site*/
			if($email != null || $numeri != null || $luogo != null){
				$iri_site = $iri_associazione.'/site/'.$count_site;
				$site = $graph->resource($iri_site, 'org:Site');
				
				/*Creo l'oggetto Location*/
				$iri_location = $iri_associazione.'/location/'.$count_location;
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
						$num_type = $n['tipo'];
						$numero = $graph->resource("tel:".$n['numero']);
						$telefono = $graph->resource($iri_location."/number/".$n['numero'],"vcard:Voice");
						$telefono->add('vcard:hasValue',$numero);
						$location->addResource("vcard:hasTelephone",$telefono);
					}
						
				}
				if($luogo != null){
					if(empty($luogo['comune']) !== false)
						$locality = $luogo['comune'];
					else
						$locality = $luogo['provincia'];
					$address = $graph->resource($iri_location."/address/". parse_string($locality),"vcard:Work");
					$address->set("vcard:country-name","Italia");
					$address->set("vcard:region",$luogo['regione']);
					$address->set("vcard:postal-code",$luogo['cap']);
					$address->set("vcard:locality",$luogo['comune']);
					$location->addResource("vcard:hasAddress",$address);
				}
				
				/*Collego il Site alla Location*/
				$site->addResource("org:siteAddress",$location);
				
				/*Collego l'Organization al Site*/
				$associazione->addResource("org:hasSite",$site);
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
		$csv_associazioni = new parseCSV();
		$csv_numeri = new parseCSV();
		$csv_email = new parseCSV();
		$csv_as_cat = new parseCSV();
		
		$csv_associazioni->delimiter = ",";
		$csv_numeri->delimiter = ",";
		$csv_email->delimiter = ",";
		$csv_as_cat->delimiter = ",";
		$csv_associazioni->parse('../src/associazioni.csv');
		$csv_numeri->parse('../src/elenco_numeri.csv');
		$csv_email->parse('../src/elenco_email.csv');
		$csv_as_cat->parse('../src/associazioni_categorie.csv');
		
		$data_associazioni = $csv_associazioni->data;
		$data_numeri = $csv_numeri->data;
		$data_email = $csv_email->data;
		$data_ass_cat = $csv_as_cat->data;
		
		//$count = 0;
		foreach($csv_associazioni->data as $row){
			$count++;
			$associazione = array();
			//Nome e Sito
			$associazione['sito'] = $row['sito'];
			$associazione['nome'] = $row['nome associazione'];
			//Informazioni sul luogo
			
			if(empty($row['comune']) && empty($row['provincia']) && empty($row['regione']) && empty($row['cap'])){
				$associazione['luogo'] = null;
			}
			else{
				$associazione['luogo'] = array();
				$associazione['luogo']['comune'] = $row['comune'];
				$associazione['luogo']['provincia'] = $row['provincia'];
				$associazione['luogo']['regione'] = $row['regione'];
				$associazione['luogo']['cap'] = $row['cap'];
			}
					
			//Contatti email		
			$associazione['email'] = array();
				foreach($data_email as $email){
					if(strcmp($email['sito associazione'],$associazione['sito']) == 0)
						array_push($associazione['email'],$email['email']);
				}
				if(count($associazione['email']) == 0)
					$associazione['email'] = null;
			//Contatti telefonici		
			$associazione['numeri'] = array();
				foreach($data_numeri as $numeri){
					if(strcmp($numeri['sito associazione'],$associazione['sito']) == 0){
						$num_type_code = $numeri['tipo'];
						switch ($num_type_code){
							case 0: 
							case 2:
								$num_type = "Voice";
							break;
							case 1: 
								$num_type = "Cell";
							break;
							default:
								$num_type = "Voice";
						}
						$n = array();
						$n['numero'] = str_replace(" ",'',$numeri['telefono']);
						$n['tipo'] = $num_type;
						array_push($associazione['numeri'],$n);
					}
						
				}
				if(count($associazione['numeri']) == 0)
					$associazione['numeri'] = null;	
			//Categorie	
			$associazione['categorie'] = array();
				foreach($data_ass_cat as $categoria){
					if(strcmp($categoria['sito associazione'],$associazione['sito']) == 0)
						array_push($associazione['categorie'],str_replace(' ', '_', $categoria['categoria']));
				}
				if(count($associazione['categorie']) == 0)
					$associazione['categorie'] = null;
			
			array_push($elenco,$associazione);
			/*if($count == 5)
				break;*/
		}
		
		return $elenco;
	}
	
	$format = 'turtle';

?>
<pre style="margin: 0.5em; padding:0.5em; background-color:#eee; border:dashed 1px grey;">
<?php
    $data = $graph->serialise($format);
    if (!is_scalar($data)) {
        $data = var_export($data, true);
    }
    print htmlspecialchars($data);
?>
</pre>
</body>
</html>