<?php
	/*
	Script per la ricerca dei siti delle associazioni.
	Lo script può essere eseguito da linea di comando utilizzando la seguente sintassi:
		php ricerca_siti.php "input #1" "input #2" ... "input #n"
		
	Oppure lo script può essere richiamato dal browser utilizzando il file Crawler.php
	
	*/

	require_once '../vendor/autoload.php';
	session_start();
	include("esamina_siti.php");
	
	$date = date('d-m-Y h:i', time());
	$client = new Google_Client();
	$client->setApplicationName("findOnlus");
	$client->setDeveloperKey("AIzaSyBIyl4IQXYUJz_JX7MWWGtJE1YKfkmTqZo");
	$search = new Google_Service_Customsearch($client);
	
	$elenco_siti = array();
	
	/*Lo script è eseguito da linea di comando*/
	if(isset($argv[1])){
		unset($argv[0]);
		$filter="";
		foreach($argv as $f){
			$filter = $f;
			fwrite($log_file,"Log generato in data: ".$date);
			fwrite($log_file,"\n Termine di input: ".$filter);
			for($i=0;$i<4;$i++){
				$start = ($i*10)+1;
				$result = $search->cse->listCse($filter, array(
					'cx' => "002086684897779538086:ojni3tynjbk",'start'=>$start 
				));
				foreach ($result->items as $res){
					array_push($elenco_siti,"http://".$res['formattedUrl']);
				}
			}

			esamina($elenco_siti);
			$elenco_siti = array();
		}
		fwrite($log_file,"\n\n Numero di risultati ottenuti: ".$num_risultati);
		fwrite($log_file,"\n Numero di risultati scartati: ".$num_scartati);
		fwrite($log_file,"\n Numero di risultati salvati: ".$num_salvati);
		fclose($log_file);
		
	}
	/*Lo script è eseguito da Browser*/
	else if(isset($_POST['input_search'])){
		$query = $_POST['input_search'];
		if(isset($_REQUEST['regioni'])){
			$regioni = $_REQUEST['regioni'];
			$filtri = "";
			if(isset($_REQUEST['province'])){
				$province = $_REQUEST['province'];
			}
			for($i=0;$i<count($regioni);$i++){
				if($i >0)
					$filtri .= " OR ";
				$found = false;
				$reg = explode("_", $regioni[$i]);
				if(isset($_REQUEST['province'])){
					for($z=0;$z<count($province);$z++){
						$prov = explode("_", $province[$z]);
						if($prov[1] == $reg[1]){
							if($found == true){
								$filtri.= " OR ";
							}
							$filtri .= $query." ".$prov[0];
							$found = true;
						}
					}
				}
				if(!$found)
					$filtri .= $query." ".$reg[0];
			}
			$query = " ".$filtri;
		}	
		fwrite($log_file,"Log generato in data: ".$date);
		fwrite($log_file,"\n Termine di input: ".$filtri);
		for($i=0;$i<4;$i++){
			$start = ($i*10)+1;
			$result = $search->cse->listCse($query, array(
				'cx' => "002086684897779538086:ojni3tynjbk",'start'=>$start 
			));
			foreach ($result->items as $res){
				array_push($elenco_siti,"http://".$res['formattedUrl']);
			}
		}
	
		esamina($elenco_siti);
		fwrite($log_file,"\n\n Numero di risultati ottenuti: ".$num_risultati);
		fwrite($log_file,"\n Numero di risultati scartati: ".$num_scartati);
		fwrite($log_file,"\n Numero di risultati salvati: ".$num_salvati);
		echo ($log_file);
		fclose($log_file);
	
	}
	/*Lo script non è stato chiamato correttamente*/
	else{
		echo "Errore! Non sono stati inseriti tutti i parametri richiesti! [NOTA] Uso da linea di comando: php ricerca_siti.php 'argument 1' 'argument 2' .. 'argument n'";
	}
	


?>