<?php
	require_once ("../lib/simple_html_dom.php");
	# include parseCSV class.
	require_once '../lib/parsecsv.lib.php';
	# include stemmer class_alias
	require_once '../lib/stemmer/stem.class.php';
	# include Alchemyapi lib
	require_once '../lib/alchemyapi.php';
	
	require_once ("database_manager.php");
	
	set_time_limit(0);
	$stemmer = new ItalianStemmer();
	$total_count = 0;
	/*
	$db = new Db();
	$query = "SELECT * from elenco_siti where elenco_siti.sito not in (SELECT sito from associazioni_categorie)";
	$result = $db->select($query);
	foreach($result as $row){
		$link = $row['Sito'];
		//$link = "http://www.ramazzini.org/";
		$result = trova_categorie($link);
		if(count($result)>0){
			$total_count++;
				$value = max($result);
				$key = array_keys($result,$value);
				foreach($key as $c){
					echo "<br>cod cat: ".$c. " --- COUNT: ".$result[$c];
					$query = "INSERT INTO associazioni_categorie VALUE('".$link."', '".$c."');";
					$db->query($query);	
				}
		}
	}
	echo "TOTALE: ".$total_count;*/
	
	/*Ricerca la categoria per un'associazione
		@param link: link del sito dell'associazione
		
		@return: elenco delle categorie trovate (null altrimenti)
	*/
	function trova_categorie($link){
		global $stemmer;
		$categorie = get_category_list();
		$keywords = get_keywords($link);
		if(array_key_exists('keywords',$keywords) === true){
			foreach ($keywords['keywords'] as $k) {
				$stemmed_word = $stemmer->stem($k['text']);
			}
			$result = associa_categoria($keywords,$categorie);
			if(count($result)>0){
				return $result;
			}
			else
				return null;
		}
		return null;
	}
	/*Associa una categoria in base alle parole chiave
		@param keywords: elenco delle parole chiave di un sito
		@param categorie: elenco delle categorie
	*/
	function associa_categoria($keywords,$categorie){
		global $stemmer;
		$count_cat = array();
		/*Cicla per ogni categoria, su le parole ad essa associata*/
		foreach($categorie as $a_key => $cat){	
			foreach($cat as $my_k){
				$my_k_stem = $stemmer->stem($my_k);
				if(empty($my_k_stem) === true)
					$my_k_stem = $my_k;
				/*Confronto ogni parola associata ad una categoria con le keywords trovate*/
				foreach ($keywords['keywords'] as $k) {
					$str_ex = explode(" ",$k['text']);
					foreach ($str_ex as $parola){						
						$parola = $stemmer->stem($parola);
						if(empty($parola) === false){
							/*Se trovo una corrispondenza, aumento il contatore di risultati per la categoria di riferimento*/
							if(strpos(strtolower($parola),$my_k_stem) === 0){
								echo "<br>Corrispondenza: ".$k['text']."  ---  ".$my_k;
								if(array_key_exists($a_key,$count_cat))
									$count_cat[$a_key]++;
								else{
									$count_cat[$a_key]= 1;
								}
							}
						}
					}
					
				}
			}
		
		}
		return $count_cat;
	}
	
	/*Recupera le parole chiave associate ad un sito
		@param link: link del sito da analizzare
		
		@return: l'elenco delle parole chiave
	*/
	function get_keywords($link){
		$alchemyapi = new AlchemyAPI();
		$url = "";
		$html = file_get_html($link);
		if(is_object($html)){
			/*Ricerca nella pagina "Chi siamo" o "Storia" dove solitamente ci sono più informazioni*/
			$link_descrizione = $html->find("a[href*=siamo],a[href*=storia],a[href*=associazione]");
			if(count($link_descrizione) > 0){
				foreach($link_descrizione as $l){
					if(stripos($l->href,"dove") === false){
						$url = $link_descrizione[0]->href;
						break;
					}
				}
			}
			else{
				$link_descrizione = $html->find("a");
				foreach($link_descrizione as $a){
					if(stripos(strtolower($a->innertext),"chi siamo") !== false || stripos(strtolower($a->innertext),"storia") !== false || stripos(strtolower($a->innertext),"associazione") !== false){
						$url = $a->href;
						break;
					}
				}
			}
			if($url == ""){
				$response = $alchemyapi->keywords('url', $link, array('maxRetrieve'=>20));
				echo "SITO: ".$link."<br>";
			}
			else{
				$url = get_absolute_url2($url,$link);
				$response = $alchemyapi->keywords('url', $url, array('maxRetrieve'=>20));
				echo "SITO: ".$url."<br>";
				
			}
			foreach ($response['keywords'] as $k) 
					echo "<br> ".$k['text'];
			if(count($response) > 0)
				return $response;
			else
				return null;
		}
		return null;
	}
	
	/*Trasforma un link relativo in assoluto
		@param link: link da trasformare
		@param dominio: dominio del sito
		
		@return: il link assoluto
	*/
	function get_absolute_url($link_contatti,$dominio){
	echo "LINK: ".$link_contatti;
	echo "<br>DOM: ".$dominio;
		if(strpos($link_contatti, $dominio) === false){
		echo "dentro if";
			$returnValue = parse_url($dominio, PHP_URL_PATH);
			/*Non ha lo slash finale*/
			if($returnValue == null){
				if(substr($link_contatti,0,1) == "/"){
					$link_contatti = $dominio .$link_contatti;
				}
				else{
					$link_contatti = $dominio . "/" . $link_contatti;
				}
				
				echo "<br>".$link_contatti." ---------- topo<br>";
			}
			/*Ha qualche path dopo il dominio*/
			else if(strlen($returnValue)>1){
				echo $link_contatti." ---------- prima<br>";
				$last_slash = strrpos($dominio,"/");
				if(substr($link_contatti,0,1) == "/"){
					$link_contatti = substr($dominio,0,$last_slash).$link_contatti; echo "qui";}
				else
					$link_contatti = substr($dominio,0,$last_slash+1).$link_contatti;
				echo $link_contatti." --------- dopo<br>";
			
	
			}
			/*Ha solo lo slash finale*/
			else{
				if(substr($link_contatti,0,1) == "/"){
					$link_contatti = $dominio . substr($link_contatti,1,strlen($link_contatti));
				}
				else{
					$link_contatti = $dominio . $link_contatti;
				}
				
				echo "<br>".$link_contatti." ---------- topo<br>";
			}
		}
		return $link_contatti;
	}
	
	/*Recupera l'elenco delle categorie
		@return: array con l'elenco delle categorie e relative parole chiave associate
	*/
	function get_category_list(){
		$categorie = array();
		$last_codice = "";
		$csv_file = new parseCSV();
		$csv_file->auto('../src/categorie.csv');
		foreach ($csv_file->data as $key => $row){
			$codice = $row['categoria'];
			if(strcmp($codice,$last_codice) == 0){
				array_push($categorie[$codice],$row['keyword']);
			}
			else{
				$categorie[$codice] = array();
				array_push($categorie[$codice],$row['keyword']);
				$last_codice = $codice;
			}
		}
		
		return $categorie;
	
	}

?>