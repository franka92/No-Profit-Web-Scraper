<?php
	require_once '../lib/parsecsv.lib.php';
	
	function crea_data_csv(){

		$db = new Db();

		$dir_path = '../data/csv';
		$file_associazioni = fopen($dir_path."/associazioni.csv", "w");
		$file_email = fopen($dir_path."/elenco_email.csv", "w");
		$file_numeri = fopen($dir_path."/elenco_numeri.csv", "w");
		$file_ass_cat = fopen($dir_path."/associazioni_categorie.csv", "w");
		$file_elenco = fopen($dir_path."/elenco.csv", "w");
		fputcsv($file_associazioni,explode(",","codice,nome associazione,sito,comune,cap,provincia,regione,indirizzo"));
		fputcsv($file_email,explode(",","sito associazione,email,tipo"));
		fputcsv($file_numeri,explode(",","sito associazione,telefono,tipo"));
		fputcsv($file_ass_cat,explode(",","sito associazione,categoria"));
		fputcsv($file_elenco,explode(",","Sito,Timestamp"));


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
		
		$query = "SELECT * FROM associazioni_categorie";
		$result = $db -> select($query);
		foreach ($result as $line){
			fputcsv($file_ass_cat,$line);
		}
		
		$query = "SELECT * FROM elenco_siti";
		$result = $db -> select($query);
		foreach ($result as $line){
			fputcsv($file_elenco,$line);
		}
		
		fclose($file_associazioni);
		fclose($file_email);
		fclose($file_numeri);
		fclose($file_ass_cat);	
		fclose($file_elenco);	
		
	}
		

	
	
	
	
?>