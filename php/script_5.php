<?php
	//include ("/lib/simple_html_dom.php");
	ini_set('default_charset', 'utf-8');
	include ("cerca_informazioni.php");
	set_time_limit(0);
$elenco = array();
	$dati = $csv->data;
	/*Ciclo sui primi n siti*/
	for($i=(4*$siti_script); $i<=(5*$siti_script);$i++){
		$link = $dati[$i]['Sito'];
		$timestamp = $dati[$i]['Timestamp'];
		/*Cerco le informazioni solo se è passato più di un mese dall'ultimo controllo*/
		if(verifica_timestamp($timestamp) === true)
			$elenco = findInformation($link,$elenco);
	}
	
	//stampaElenco($elenco);
		stampaElenco($elenco);
		echo "<br>5 - ********* TEMPO ".date('i:s', time()-$tempo_iniziale);
	//echo json_encode($elenco);
	/*foreach ( as $key => $row){
		$link = $row['Sito'];
		$nome = $row['Nome'];
		$info_trovate = false;
		
		$elenco = findInformation($link,$elenco);
		
	}*/
	



?>