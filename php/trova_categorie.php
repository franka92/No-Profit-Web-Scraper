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
	
	/*$db = new Db();
	$query = "SELECT * from elenco_siti LIMIT 20,20";
	$result = $db -> select($query);
	foreach($result as $row){
		$link = $row['Sito'];
		$keywords = get_keywords($link);
		if(array_key_exists('keywords',$keywords) === true){
			foreach ($keywords['keywords'] as $k) {
				$stemmed_word = $stemmer->stem($k['text']);
			}
			$result = associa_categoria($keywords,$categorie);
			if(count($result)>0){
				$total_count++;
					$value = max($result);
					$key = array_keys($result,$value);
					foreach($key as $c){
						//echo "<br>cod cat: ".$c. " --- COUNT: ".$result[$c];
						$query = "INSERT INTO associazioni_categorie VALUE('".$link."', '".$c."');";
						$db->query($query);	
					}
			}
		}
	}
	echo "TOTALE: ".$total_count;*/
	
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
	
	function associa_categoria($keywords,$categorie){
		global $stemmer;
		$count_cat = array();
		foreach($categorie as $a_key => $cat){	
			//echo "<br>Codice: ".$a_key;
			foreach($cat as $my_k){
				//echo "MIA PAROLA: ".$my_k;
				$my_k_stem = $stemmer->stem($my_k);
				if(empty($my_k_stem) === true)
					$my_k_stem = $my_k;
				//echo " --- STAMMED: ".$my_k_stem."<br>";
				foreach ($keywords['keywords'] as $k) {
					$str_ex = explode(" ",$k['text']);
					foreach ($str_ex as $parola){
						//echo "<br>PAROLA: ".$parola;
						
						$parola = $stemmer->stem($parola);
						if(empty($parola) === false){
							//echo "  ---  STEMMED: ".$parola;
							if(strpos(strtolower($parola),$my_k_stem) === 0){
							//if(strcmp(strtolower($parola),$my_k_stem) == 0){
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
	

	function get_keywords($link){
		$alchemyapi = new AlchemyAPI();
		$url = "";
		$html = file_get_html($link);
		if(is_object($html)){
			$link_descrizione = $html->find("a[href*=siamo],a[href*=storia]");
			if(count($link_descrizione) > 0){
				foreach($link_descrizione as $l){
					if(stripos($l->href,"dove") === false){
						$url = $link_descrizione[0]->href;
						break;
					}
				}
				
				//$r = checkForCategory(file_get_html($link_descrizione[0]->href), $found = array(), $search_for);
			}
			else{
				$link_descrizione = $html->find("a");
				foreach($link_descrizione as $a){
					if(stripos(strtolower($a->innertext),"chi siamo") !== false){
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
				$url = get_absolute_url($url,$link);
				$response = $alchemyapi->keywords('url', $url, array('maxRetrieve'=>20));
				echo "SITO: ".$url."<br>";
				
			}
			if(count($response) > 0)
				return $response;
			else
				return null;
		}
		return null;
	}
	
	function get_absolute_url($link,$dominio){
		if(strpos($link,$dominio) === false){
			if(substr($link,0,1) == "/"){
				$link = $dominio . substr($link,1,strlen($link));
			}
			else{
				$link = $dominio . $link;
			}
		}
		return $link;
	}
	
	
	
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