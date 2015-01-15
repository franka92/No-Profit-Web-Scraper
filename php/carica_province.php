<?php
	# include parseCSV class.
	require_once '../lib/parsecsv.lib.php';

	//Recupero i dati dal file .csv
	$csv = new parseCSV();
	$csv->auto('../src/ripartizioni_regioni_province.csv');
	
	$province = array();
	
	$codice_regione = $_REQUEST['cod_reg'];
	
	/*Per ogni provincia associata alla regione, recupero Nome e Codice e inserisco i valori in un array*/
	foreach ($csv->data as $key => $row){
		$regione = $row["Codice regione"];
		if($regione == $codice_regione){
			$nome = $row["Denominazione provincia"];
			$codice = $row["Codice provincia"];
			$array = array();
			$array['nome'] = $nome;
			$array['codice'] = $codice;
			$array = array_map('utf8_encode', $array);
			if(in_array($array,$province) === false){
				
				array_push($province,$array);
			}
		}
		
		
	}
	/*Restituisco alla chiamata Ajax i risultati trovati*/
	echo json_encode($province);
?>