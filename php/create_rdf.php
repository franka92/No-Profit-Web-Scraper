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
	$cat = "";
	$csv_file = new parseCSV();
	$csv_file->auto('../src/categorie.csv');
		
	$graph = new EasyRdf_Graph();
	EasyRdf_Namespace::set('org', 'http://www.w3.org/ns/org#'); 
	EasyRdf_Namespace::set('npd', 'http://www.no-profit-data.it/res/'); 
	
	foreach ($csv_file->data as $key => $row){
		$cur_cat = $row['categoria'];
		if($cur_cat == $cat){
			
		}
		else{
			$parse_cat = str_replace(' ', '_', $cur_cat);
			$categoria = $graph->resource('http://www.no-profit-data.it/res/categorie/'.$parse_cat, 'skos:Concept');
			$categoria->set('rdfs:label',$cur_cat);
		}
		
	}
	
	$elenco_associazioni = recupera_elenco_associazioni();
	crea_grafo($elenco_associazioni);
	

	
	
	function crea_grafo($elenco_associazioni){
	global $graph;
		//$graph = new EasyRdf_Graph();
		$prefix = "http://www.no-profit-data.it/res/";
		//EasyRdf_Namespace::set('org', 'http://www.w3.org/ns/org#'); 
		//EasyRdf_Namespace::set('npd', 'http://www.no-profit-data.it/res/'); 
		
		
		foreach($elenco_associazioni as $dati_a){
			$link = $dati_a['sito'];
			$nome = $dati_a['nome'];
			$email = $dati_a['email'];
			$numeri = $dati_a['numeri'];
			$categorie = $dati_a['categorie'];
			$luogo = $dati_a['luogo'];
			
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
			foreach($categorie as $cat){
				$associazione->addResource("org:purpose",$cat);
			}
			
			/*Creo l'oggetto Site*/
			if($email != null || $numeri != null || $luogo != null){
				$iri_site = $iri_associazione.'/site/1';
				$site = $graph->resource($iri_site, 'org:Site');
				
				/*Creo l'oggetto Location*/
				$iri_location = $iri_associazione.'/location/1';
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
					$address = $graph->resource($iri_location."/address/".$luogo['comune'],"vcard:Work");
					$address->set("vcard:country-name","Italia");
					$address->set("vcard:region",$luogo['regione']);
					$address->set("vcard:postal-code:",$luogo['cap']);
					$address->set("vcard:locality",$luogo['comune']);
					$location->addResource("vcard:hasAddress",$address);
				}
				
				/*Collego il Site alla Location*/
				$site->addResource("org:siteAddress",$location);
				
				/*Collego l'Organization al Site*/
				$associazione->addResource("org:hasSite",$location);
			}
						
		}
		
	
		
		}
	
	
	function parse_string($stringa){
		$parse_cat = str_replace(' ', '_', $stringa);
	
	}
	
	
	
	
	
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
		$csv_as_cat->parse('../src/elenco_ass_cat.csv');
		
		$data_associazioni = $csv_associazioni->data;
		$data_numeri = $csv_numeri->data;
		$data_email = $csv_email->data;
		$data_ass_cat = $csv_as_cat->data;
		
		$count = 0;
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
					if($email['sito'] == $associazione['sito'])
						array_push($associazione['email'],$email['email']);
				}
				if(count($associazione['email']) == 0)
					$associazione['email'] = null;
			//Contatti telefonici		
			$associazione['numeri'] = array();
				foreach($data_numeri as $numeri){
					if($numeri['sito'] == $associazione['sito']){
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
					if($categoria['sito'] == $associazione['sito'])
						array_push($associazione['categorie'],str_replace(' ', '_', $categoria['categoria']));
				}
				if(count($associazione['categorie']) == 0)
					$associazione['categorie'] = "sconosciuta";
			
			array_push($elenco,$associazione);
			if($count == 2)
				break;
		}
		
		return $elenco;
	}
	
	
	


	
	/*$categoria = $graph->resource('http://www.no-profit-data.it/categorie/musica', 'skos:Concept');
	$categoria->set('rdfs:label','musica');*/
	
	$format = 'turtle';
	
	/*$foaf = EasyRdf_Graph::newAndLoad(null,null);
	$foaf->load();
	$me = $foaf->primaryTopic();
	echo "My name is: ".$me->get('foaf:name')."\n";*/
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