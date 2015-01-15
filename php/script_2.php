<?php
	//include ("/lib/simple_html_dom.php");
	include ("cerca_informazioni.php");
	ini_set('default_charset', 'utf-8');
	
	set_time_limit(0);
$elenco = array();
	$dati = $csv->data;
	/*Ciclo sui primi n siti*/
	for($i=($siti_script); $i<(2*$siti_script);$i++){
		$link = $dati[$i]['Sito'];
		$elenco = findInformation($link,$elenco);
	}
	
	//stampaElenco($elenco);
	//stampaElenco($elenco);
		echo "<br>2 - ********* TEMPO ".date('i:s', time()-$tempo_iniziale);
	//echo json_encode($elenco);
	/*foreach ( as $key => $row){
		$link = $row['Sito'];
		$nome = $row['Nome'];
		$info_trovate = false;
		
		$elenco = findInformation($link,$elenco);
		
	}*/
	



?>