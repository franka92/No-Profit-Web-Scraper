<?php
	//include ("/lib/simple_html_dom.php");
	# include parseCSV class.
	//require_once '/lib/parsecsv.lib.php';
	
	include ("cerca_informazioni.php");
	//include ("script_2.php");
	//include ("script_3.php");
	
	ini_set('default_charset', 'utf-8');
	
	set_time_limit(0);
	$elenco = array();
	$dati = $csv->data;
	/*Ciclo sui primi n siti*/
	for($i=0; $i<$siti_script;$i++){
		$link = $dati[$i]['Sito'];
		$timestamp = $dati[$i]['Timestamp'];
		if ($timestamp == null){/*Il sito non è mai stato analizzato*/ 
			$elenco = findInformation($link,$elenco);
			if($elenco != null){/*Salvo i dati nel file .csv*/
				scrivi_file($elenco);
			}
			/*Devo aggiornare il timestamp*/
		}
		else{
			/*Cerco le informazioni solo se è passato più di un mese dall'ultimo controllo*/
			if(verifica_timestamp($timestamp) === true){
				$elenco = findInformation($link,$elenco);
				//scrivi_file($elenco);
				/*Devo aggiornare il timestamp
					Confronto tra dati attuali e quelli di prima?
				*/
			}
			else{/*Altrimenti mantengo le informazioni precedenti*/
				/*Cerco sul database il sito, e mi creo l'oggetto "elenco" prendendo tali dati*/
			}
		}
		
	}
	
	//stampaElenco($elenco);
	echo "<br>1 - ********* TEMPO ".date('i:s', time()-$tempo_iniziale);
	//echo json_encode($elenco);
	
	/*foreach ( as $key => $row){
		$link = $row['Sito'];
		$nome = $row['Nome'];
		$info_trovate = false;
		
		$elenco = findInformation($link,$elenco);
		
	}*/
	



?>