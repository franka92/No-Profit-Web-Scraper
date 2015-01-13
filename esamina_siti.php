<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php

	include ("stampa_info.php");
	set_time_limit(0);
	
	header('Content-Type: text/html; charset=ISO-8859-1');
	ini_set('default_charset', 'utf-8');

	$a = json_decode($_REQUEST['valori'],true);
	$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
	foreach ($a['sito'] as $key => $obj){
		
		$parse = parse_url($obj['link']);
		$link =  "http://".$parse['host'];
		$nome = $obj['nome'];
		/*Il sito non è già presente nell'elenco*/
		if(cercaSitoElenco($link) == false){
			$dominio = $link;
			if(siteFilter($link)){/*Il sito riguarda una pagina social o siti che non ci interessano*/
				unset($a['sito'][$key]);
			}
			else{
				$result = getContent($link);
				libxml_use_internal_errors(TRUE);
				if($result != null){
					$search_for = array();
					$r = checkForText($result, $found = array(), $search_for);
					if ($r >1) {echo "<b>".$dominio ."</b><br>";
						foreach($r as $content){
							fwrite($myfile,"trovato: ".$content." --- ");
						}
						fwrite($myfile, $obj['nome']);
						fwrite($myfile, " ____ ".$link."\n");
					}
					else{/*Il sito non risponde ai termini ricercato --> lo scarto*/
						unset($a['sito'][$key]);
					}
				}
				else{/*Non riesco ad aprire il link --> scarto il sito*/
					unset($a['sito'][$key]);
				}
				
				
			}
			
			
		}
		else{/*Il sito è già presente nell'elenco, quindi salto al prossimo*/
		
		}
		

	}

	/*A questo punto ho già un primo elenco filtrato*/
	foreach ($a['sito'] as $key => $obj){
		fwrite($myfile, $obj['nome']);
		fwrite($myfile, $link."\n");
	}
	fclose($myfile);
	
	/*ora dovrei fare le ricerche sia sui siti che ho trovato, sia su quelli che ho già salvato sui miei file .csv*/
	$csv_file = fopen("src/elenco.csv", "a");
	foreach ($a['sito'] as $key => $obj){
		$parse = parse_url($obj['link']);
		$link =  "http://".$parse['host'];
		if(cercaSitoElenco($link) == false){
			$elenco = findInformation($link,$elenco);

			fputcsv($csv_file, array('Sito' => $link, 'Nome' => ''));
		}

	}
	fclose($csv_file);
	stampaElenco($elenco);

	
	function cercaSitoElenco($link){
		$csv_file = new parseCSV();
		$csv_file->auto('src/elenco.csv');
		foreach ($csv_file->data as $key => $row){
			if(strcmp($row['Sito'],$link) == 0)
				return true;
		}
		return false;
	
	}

	function siteFilter($url){
		$sites= array("facebook","twitter","youtube","wikipedia");
		foreach ($sites as $word){
			if (strpos($url,$word) != false)
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
			if(preg_match("/".$test."/i", $page)){
				array_push($found,$test);
			}
		}
		return $found;
	}

		

?>