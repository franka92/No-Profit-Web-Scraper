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
		$nome = $dati[$i]['Nome'];
		$elenco = findInformation($link,$elenco);
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