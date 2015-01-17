<?php
	require_once '../lib/parsecsv.lib.php';
	include ("database_manager.php");
	
	$db = new Db();
	$query = "SELECT * FROM elenco_siti WHERE Timestamp is NULL";
	$result = $db -> select($query);
	$response = array();
	/*Qualche script non ha terminato --> rilancio*/
	if(count($result)>0){
		$response['value'] = "false";
		echo ("no");
	} 
	else{
		if (!file_exists('../data/esecuzione_'.time())) {
			$dir_path = '../data/esecuzione_'.time();
			mkdir($dir_path, 0777, true);
			$file_associazioni = fopen($dir_path."/associazioni_".time().".csv", "w");
			$file_email = fopen($dir_path."/elenco-email_".time().".csv", "w");
			$file_numeri = fopen($dir_path."/elenco-numeri_".time().".csv", "w");
			$file_elenco = fopen("../src/elenco.csv", "w");
			fputcsv($file_associazioni,explode(",","codice,nome associazione,sito,comune,cap,provincia,regione,categoria"));
			fputcsv($file_email,explode(",","codice,sito associazione,email"));
			fputcsv($file_numeri,explode(",","codice,sito associazione,telefono"));
			fputcsv($file_numeri,explode(",","Sito,Timestamp"));
	
	
			$query = "SELECT * FROM associazioni";
			$result = $db -> select($query);
			foreach ($result as $line){
				fputcsv($file_associazioni,$line);
			}
			
			$query = "SELECT * FROM elenco_email";
			$result = $db -> select($query);
			foreach ($result as $line){
				fputcsv($file_email,$line);
			}
			
			$query = "SELECT * FROM elenco_numeri";
			$result = $db -> select($query);
			foreach ($result as $line){
				fputcsv($file_numeri,$line);
			}
			
			$query = "SELECT * FROM elenco_siti";
			$result = $db -> select($query);
			foreach ($result as $line){
				fputcsv($file_elenco,$line);
			}
			
			fclose($file_associazioni);
			fclose($file_email);
			fclose($file_numeri);
			fclose($file_elenco);	
		}
		
		$response['value'] = "true";
		echo ("ok");
	}
	
		

	
	
	
	
?>