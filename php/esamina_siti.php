<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php

	include ("../lib/simple_html_dom.php");
	# include parseCSV class.
	require_once '../lib/parsecsv.lib.php';
	# include stemmer class_alias
	require_once '../lib/stemmer/stem.class.php';
	# include Alchemyapi lib
	require_once '../lib/alchemyapi.php';
	include ("database_manager.php");
	set_time_limit(0);
	
	ini_set('default_charset', 'utf-8');
	
	$log_file = fopen("../log/log_".time().".txt","a");
	$num_risultati = 0;
	$num_scartati = 0;
	$num_salvati = 0;


	/*Esamina i siti per vedere se sono possibili siti di associazioni o no
		@param a: array contenente i link dei siti
	*/
	function esamina($a){
		global $log_file;
		global $num_risultati;
		global $num_scartati;
		global $num_salvati;
		$stemmer = new ItalianStemmer();
		$num_risultati += count($a);
		$parole_chiave = array ('associazione','onlus','profit','volontariato','organizzazione','cooperativa');
		foreach ($a as $key => $obj){
			$da_scartare = false;
			/*Risalgo alla homePage del sito*/
			$parse = parse_url($obj);
			$dominio = $parse['host'];
			$link =  "http://".$parse['host'];
			/*Il sito non è già presente nell'elenco*/
			if(cercaSitoElenco($link) === false && count(array_keys($a,$link)) <= 1){
				if(siteFilter($link)){/*Il sito riguarda una pagina social o siti che non ci interessano*/
					$da_scartare = true;
				}
				else{
					$parse = parse_url($link);
					$homepage = $parse['host'];		
					$keywords = get_keywords($link);
					if($keywords != null && array_key_exists('keywords',$keywords) === true){
						$result = cerca_corrispondenza($keywords,$parole_chiave);
						if($result == 0){
							$da_scartare = true;
						}
					}
					else{
						$da_scartare = true;
					}
				}
			}
			else{
				$da_scartare = true;
			}
			
			if($da_scartare === true){
				unset($a[$key]);
				fwrite($log_file,"\n Sito scartato: ".$link);
				print "Scartato: ".$link;
				$num_scartati++;
			}
			else{
				print "Inserito: ".$link;
				fwrite($log_file,"\n Sito inserito: ".$link);	
				$num_salvati++;
			}
		}

		/*A questo punto ho già un primo elenco filtrato*/	
		foreach ($a as $key => $obj){
			$parse = parse_url($obj);
			$link =  "http://".$parse['host'];
			if(cercaSitoElenco($link) === false){
				/*Aggiorno il file .csv*/
				$db = new Db();
				$query= "INSERT INTO elenco_siti VALUE('".$link."', NULL)";
				$db->query($query);
				/*Aggiorno il database*/
			}

		}
		fclose($csv_file);
	}

	/*Ricerca un sito nell'elenco già salvato
		@param link: link da ricercare
		
		@return: true o false
	*/
	function cercaSitoElenco($link){
		$db = new Db();
		$res = $db->select("SELECT Sito from elenco_siti where sito='".$link."';");
		
		if(count($res)>1)
			return true;
		
		return false;
	
	}
	
	/*Filtra i siti per scartare quelli che non ci interessano
		@param url: link del sito da controllare
		
		@return: true o false
	*/
	function siteFilter($url){
		$csv_scarto = new parseCSV();
		$csv_scarto->delimiter=",";
		$csv_scarto->parse('../src/elenco_siti_scarto.csv');
		$sites = $csv_scarto->data;
		
		foreach ( $csv_scarto->data as $word){
			if (strpos($url,$word['Sito']) !== false)
				return true;
		}
		return false;
	}

	
	/*Trasforma un link relativo in assoluto
		@param link: link da trasformare
		@param dominio: dominio del sito
		
		@return: il link assoluto
	*/
	function get_absolute_url($link_contatti,$dominio){
		$dom = parse_url($dominio, PHP_URL_HOST);
		if(strpos($link_contatti, $dom) === false){
			$returnValue = parse_url($dominio, PHP_URL_PATH);
			/*Non ha lo slash finale*/
			if($returnValue == null){
				if(substr($link_contatti,0,1) == "/"){
					$link_contatti = $dominio .$link_contatti;
				}
				else{
					$link_contatti = $dominio . "/" . $link_contatti;
				}

			}
			/*Ha qualche path dopo il dominio*/
			else if(strlen($returnValue)>1){

				$last_slash = strrpos($dominio,"/");
				if(substr($link_contatti,0,1) == "/")
					$link_contatti = substr($dominio,0,$last_slash).$link_contatti;
				else
					$link_contatti = substr($dominio,0,$last_slash+1).$link_contatti;
			}
			/*Ha solo lo slash finale*/
			else{
				if(substr($link_contatti,0,1) == "/"){
					$link_contatti = $dominio . substr($link_contatti,1,strlen($link_contatti));
				}
				else{
					$link_contatti = $dominio . $link_contatti;
				}
			}
		}
		return $link_contatti;
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
			}
			else{
				$url = get_absolute_url($url,$link);
				$response = $alchemyapi->keywords('url', $url, array('maxRetrieve'=>20));
				
			}

			if(count($response) > 0)
				return $response;
			else
				return null;
		}
		return null;
	}
	
	/*Associa una categoria in base alle parole chiave
		@param keywords: elenco delle parole chiave di un sito
		@param categorie: elenco delle categorie
	*/
	function cerca_corrispondenza($keywords,$parole){
		$stemmer = new ItalianStemmer();
		$found = 0;
		/*Cicla per ogni categoria, su le parole ad essa associata*/
		foreach($parole as $a_key => $p){	
				$my_k_stem = $stemmer->stem($p);
				if(empty($my_k_stem) === true)
					$my_k_stem = $p;
				/*Confronto ogni parola associata ad una categoria con le keywords trovate*/
				foreach ($keywords['keywords'] as $k) {
					$str_ex = explode(" ",$k['text']);
					foreach ($str_ex as $parola){						
						$parola = $stemmer->stem($parola);
						if(empty($parola) === false){
							/*Se trovo una corrispondenza, aumento il contatore di risultati per la categoria di riferimento*/
							if(strpos(strtolower($parola),$p) === 0){
								$found++;
							}
						}
					}
					
				}
		
		}
		return $found;
	}

		

?>