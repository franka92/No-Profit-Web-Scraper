<?php
	# include parseCSV class.
	require_once '../lib/parsecsv.lib.php';

	//Recupero i dati dal file .csv
	$csv = new parseCSV();
	$csv->auto('../src/ripartizioni_regioni_province.csv');
	
	$regioni = array();
	
	/*Per ogni regione, recupero Nome e Codice e inserisco i valori in un array*/
	foreach ($csv->data as $key => $row){
		$nome = $row["Denominazione regione"];
		$codice = $row["Codice regione"];
		$array = array();
		$array['nome'] = $nome;
		$array['codice'] = $codice;
		$array = array_map('utf8_encode', $array);
		if(in_array($array,$regioni) === false){
			
			array_push($regioni,$array);
		}
		
	}
	/*Restituisco alla chiamata Ajax i risultati trovati*/
	echo json_encode($regioni);
?>