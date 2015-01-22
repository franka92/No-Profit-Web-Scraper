<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php

	include ("../lib/simple_html_dom.php");
	# include parseCSV class.
	require_once '../lib/parsecsv.lib.php';
	include ("database_manager.php");
	set_time_limit(0);
	
	ini_set('default_charset', 'utf-8');
	

	
	$someUA = array (
	"Mozilla/5.0 (Windows; U; Windows NT 6.0; fr; rv:1.9.1b1) Gecko/20081007 Firefox/3.1b1",
	"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.0",
	"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/0.4.154.18 Safari/525.19",
	"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27 Safari/525.13",
	"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)",
	"Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.40607)",
	"Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322)",
	"Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.0.3705; Media Center PC 3.1; Alexa Toolbar; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
	"Mozilla/45.0 (compatible; MSIE 6.0; Windows NT 5.1)",
	"Mozilla/4.08 (compatible; MSIE 6.0; Windows NT 5.1)",
	"Mozilla/4.01 (compatible; MSIE 6.0; Windows NT 5.1)");
	
	
	/*Esamina i siti per vedere se sono possibili siti di associazioni o no
		@param a: array contenente i link dei siti
	*/
	
	function esamina($a){
		foreach ($a as $key => $obj){
			echo $obj;
			$parse = parse_url($obj);
			$link =  "http://".$parse['host'];
			/*Il sito non è già presente nell'elenco*/
			if(cercaSitoElenco($link) == false){
				$dominio = $link;
				if(siteFilter($link)){/*Il sito riguarda una pagina social o siti che non ci interessano*/
					unset($a[$key]);
				}
				else{
					/*Risalgo alla homePage del sito*/
					$parse = parse_url($link);
					$homepage = $parse['host'];
					echo "SITO: ".$link." **** ".$homepage;				
					$html = file_get_html("http://".$homepage);
					if(is_object($html)){
						/*Cerco dei link alle pagine "chi siamo" o "storia"*/
						$link_chiSiamo = $html->find("a[href*=chi] , a[href*=storia]");
						/*Cerco nelle pagine "chi siamo" o "storia" dei riferimenti a delle parole chiave, per vedere se il sito è utilizzabile*/
						if(count($link_chiSiamo) > 0){
							foreach($link_chiSiamo as $element){
								$link_contatti = $element->href;
								if(substr($link_contatti,0,strlen($link)) != $homepage){
									if(substr($link_contatti,0,1) == "/" && substr($homepage,strlen($homepage)-1,strlen($homepage)) == "/"){
										$link_contatti = $homepage . substr($link_contatti,1,strlen($link_contatti));
									}
									else if(substr($link_contatti,0,1) != "/" && substr($homepage,strlen($homepage)-1,strlen($homepage)) != "/"){
										$link_contatti = $homepage . "/".$link_contatti;
									}
									else{
										$link_contatti = $homepage . $link_contatti;
									}
								}
								if($link_contatti != ""){
									$search_for = array();
									$result = file_get_html($link_contatti);
									$r = checkForText($result, $found = array(), $search_for);
									if ($r >1) {
										echo "<b>".$dominio ."</b><br>";
									}
									else{/*Il sito non risponde ai termini ricercati --> lo scarto*/
										unset($a['sito'][$key]);
									}
								}	
								else{
									echo "<br> Link contatti == null ".$dominio;
								}						
							}
						}
						else{/*Se non ho trovato dei link, effettuo la ricerca direttamente sulla homepage*/
							$result = getContent($homepage);
							libxml_use_internal_errors(TRUE);
							if($result != null){
								$search_for = array();
								$r = checkForText($result, $found = array(), $search_for);
								if ($r >1) {
									echo "<b>".$dominio ."</b><br>";
								}
								else{/*Il sito non risponde ai termini ricercati --> lo scarto*/
									unset($a['sito'][$key]);
								}
							}
							else{/*Non riesco ad aprire il link --> scarto il sito*/
								unset($a['sito'][$key]);
							}
						}

					}
				
				}	
			}
			else{/*Il sito è già presente nell'elenco, quindi salto al prossimo*/
				/*Do Nothing*/
			}
			

		}

		/*A questo punto ho già un primo elenco filtrato*/	
		$csv_file = fopen("../src/elenco_new.csv", "a");
		foreach ($a as $key => $obj){
			$parse = parse_url($obj);
			$link =  "http://".$parse['host'];
			if(cercaSitoElenco($link) === false){
				/*Aggiorno il file .csv*/
				fputcsv($csv_file, array('Sito' => $link, 'Timestamp' => 'NULL'));
				/*
				$db = new Db();
				$query= "INSERT INTO elenco_siti VALUE('".$link."', NULL)";
				$db->query($query);*/
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
		$csv_file = new parseCSV();
		$csv_file->auto('src/elenco.csv');
		foreach ($csv_file->data as $key => $row){
			if(strcmp($row['Sito'],$link) == 0)
				return true;
		}
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
			if (strpos($url,$word['Sito']) != false)
				return true;
		}
		return false;
	}
	

	/*Cerca i termini nella pagina*/
	function checkForText($page, $found = array(), $filter = array()){
		$filtri = array("Onlus","associazione","associazioni","emilia","romagna","organizzazione", "no profit");
		$filter = ((!empty($filter) && is_array($filter)) ? $filter : $filtri);
		$found = is_array($found) ? $found : array();
		foreach($filtri  as $test){
			if(preg_match("/ ".$test." /i", $page)){
				array_push($found,$test);
			}
		}
		return count($found);
	}
	
	
	function getRandomUserAgent ( ) {
		//srand((double)microtime()*1000000);
		global $someUA;
		return $someUA[rand(0,count($someUA)-1)];
	}
	function getContent ($url) {
	 
		// Crea la risorsa CURL
		$ch = curl_init();
	 
		// Imposta l'URL e altre opzioni
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, getRandomUserAgent());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		// Scarica l'URL e lo passa al browser
		$output = curl_exec($ch);
		$info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// Chiude la risorsa curl
		curl_close($ch);
		if ($output === false || $info != 200) {
		  $output = null;
		}
		return $output;
	 
	}


		

?>