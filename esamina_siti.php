<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php
/*
	SITI BUONI
	
	http://www.spaziomusica.org/
	http://www.associazionemusicaviva.it/
*/
	include ("stampa_info.php");
	set_time_limit(0);
	
	header('Content-Type: text/html; charset=ISO-8859-1');
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

	$a = json_decode($_REQUEST['valori'],true);
	$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
	foreach ($a['sito'] as $key => $obj){
		/*
			Per ognuno dei link trovati, devo controllare se può essere un sito adatto al nostro scopo.
			In particolare potrei vedere se:
				-La pagina contiene dei riferimenti a: Onlus, associazioni
				-controllare se ci sono link ad altre associazioni
				-verifricare se ci sono dei link alla home, perchè non sempre il sito che apro è indirizzato alla homepage..
				-cercare una pagina "chi siamo" e analizzarla x vedere che sito è 
				
				https://regex101.com/r/fG4tW0/1
			****************************
			DEVO TROVARE UN MODO EFFICIENTE PER CAPIRE SE IL SITO CHE STO ESAMINANDO E' UN SITO DI UN ASSOCIAZIONE, E QUINDI SE POSSO USARLO PER LA MIA ANALISI.
			LE COSE CHE MI INTERESSANO SOSTANZIALMENTE SONO QUESTE:
				-DEVE ESSERE UN SITO DI UN ASSOCIAZIONE
				-DEVE AVERE UNA SEZIONE CON DEGLI EVENTI
				-POSSIBILMENTE IN EMILIA ROMAGNA
				-DEVE AVERE DEI CONTATTI (?)
				
			1. CERCO NELLA HOMEPAGE SE C'è UN LINK AD UNA PAGINA "CHI SIAMO" O SE ESISTE UNA DESCRIZIONE DEL SITO --> ANALISI
			
			2. CERCO NELLA PAGINA UN RIFERIMENTO AD UN LINK "EVENTI" O COSE SIMILI


			****************************
				
			
			Se la pagina è utilizzabile:
			-Salvo il link nel file .csv
			-Eseguo sulla pagina la scansione per determinare :
				-categoria
				-provincia
				-contatti
			
			
			
			Potrei provare a vedere se la pagina contiene riferimenti a dei nomi, basandomi sui siti che già so essere buoni..
			escludere alcuni domini (tipo facebook, siti di giornali ecc..)
			
			Una volta deciso che il link è valido, lo inserisco nel file csv..
			Quandi ho iterato su tutti i risultati, chiamo il file stampa_info.php che esegue l'analisi sul file csv
		*/
		$parse = parse_url($obj['link']);
		$link =  "http://".$parse['host'];
		$nome = $obj['nome'];
		/*Il sito non è già presente nell'elenco*/
		if(cercaSitoElenco($link) == false){
			//echo "trovato: ".$link."<br>";
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
					//fwrite($myfile,"NON trovato: ");
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

			//$csv_new = new parseCSV();
			//$csv_new->save('src/elenco.csv', array($link, 'Home'), true);
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
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/*Potrei procedere in questo modo:
		-cerco i link alle homepage, perchè non è detto che i risultati di google siano già indirizzati alla home
		-parole chiave che possono fare riferimento ad eventi:
			-evento/i
			-programmazione
			-programma
			-corsi
			-manifestazioni
			-spettacolo/i
			-concerti
			
		-parole chiave che possono fare riferimenti alla descrizione del sito:
			-chi siamo
			-storia
			-informazioni/info
			-
			
		-parole chiave per ricercare i contatti:
			-contatti/contattaci
			-chi siamo
			-informazioni/info
	
	*/
	
	
	
	
	
	
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