<?php

	function crea_data_json(){

		$db = new Db();
		$dir_path = '../data/json';

		$file_associazioni = fopen($dir_path."/associazioni.json", "w");
		$file_elenco_siti = fopen($dir_path."/elenco_siti.json", "w");
		
		$query = "SELECT * FROM elenco_siti";
		$result = $db -> select($query);
		$elenco_assoc = array();
		$elenco_siti = array();
		foreach ($result as $line){
			$link = $line['Sito'];
			array_push($elenco_siti,$line);
			$site = recupera_dati($link);
			array_push($elenco_assoc,$site);
		}
		
		$s = json_encode($elenco_assoc);
		$str = json_encode($elenco_siti);
		
		fwrite($file_associazioni,utf8_encode($s));
		fwrite($file_elenco_siti,utf8_encode($str));
		
		fclose($file_associazioni);
		fclose($file_elenco_siti);

			
		
	}
	
	
	
	
	
	
?>