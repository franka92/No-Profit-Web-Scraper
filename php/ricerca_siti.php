<?php
	/*
	Script per la ricerca dei siti delle associazioni.
	Lo script può essere eseguito da linea di comando utilizzando la seguente sintassi:
		php ricerca_siti.php "input #1" "input #2" ... "input #n"
	
	*/

	require_once '../vendor/autoload.php';
	session_start();
	include("esamina_siti.php");
	
	
	if(isset($argv[1])){
		unset($argv[0]);
		$filter="";
		$elenco_siti = array();
		$client = new Google_Client();
		$client->setApplicationName("findOnlus");
		$client->setDeveloperKey("AIzaSyBP5J7RWSyoiviC8ISXdVOfg0PzSlUmZ8Y");
		$search = new Google_Service_Customsearch($client);
		foreach($argv as $f){
			$filter = $f;
			//$filter .=" ".$f;
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
		
	}
	else{
		echo "Error! Usage: php ricerca_siti.php 'argument 1' 'argument 2' .. 'argument n'";
	}
	


?>