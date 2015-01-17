<?php
	include ("cerca_informazioni.php");
	
	ini_set('default_charset', 'utf-8');
	
	set_time_limit(0);
	$elenco= array();
	$elenco_siti = array();
	$dati = $csv->data;
	
	/*Ciclo sui primi n siti*/
	for($i=(3*$siti_script); $i<(4*$siti_script);$i++){
		$link = $dati[$i]['Sito'];
		$timestamp = $dati[$i]['Timestamp'];
		$sito = array();
		$sito['Sito'] = $link;
		if ($timestamp == null){/*Il sito non è mai stato analizzato*/ 
			$result = findInformation($link);
			if($result === true){
				/*Devo aggiornare il timestamp*/
				$sito['Timestamp'] = time();
				array_push($elenco_siti,$sito);
			}
			else{
				echo "<br>sito cancellato: ".$link;
			}
		}
		else{
			/*Cerco le informazioni solo se è passato più di un mese dall'ultimo controllo*/
			if(verifica_timestamp($timestamp) === true){
				$result = findInformation($link);
				if($result === true){
					$sito['Timestamp'] = time();
					array_push($elenco_siti,$sito);
					echo "no<br>";
				}
				else{
					echo "<br>sito cancellato: ".$link;
				}
			}
			else{/*Altrimenti mantengo le informazioni precedenti*/
				$result = recupera_info($link,"../data/results4.json");
				if($result === true){
					echo "<br> true ".$i;
					$sito['Timestamp'] = $timestamp;
					array_push($elenco_siti,$sito);
				}
				else{
					echo "<br>sito cancellato: ".$link;
				}
			}
		}
		
	}
	$file_path = "../data/results4.json";
	if(file_exists($file_path)){
		unlink ($file_path);
	}
	$fp = fopen($file_path, 'w');
	fwrite($fp, json_encode($elenco));
	fclose($fp);
	
	$file_path = "../src/elenco4.json";
	if(file_exists($file_path)){
		unlink ($file_path);
	}
	$fp = fopen($file_path, 'w');
	fwrite($fp, json_encode($elenco_siti));
	fclose($fp);
	echo "done";
	
?>