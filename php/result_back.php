<?php

	require_once '../vendor/autoload.php';
	include("esamina_siti.php");

	$regioni = $_REQUEST['regioni'];
	$province = $_REQUEST['province'];
	$filtri = "";
	$query = $_POST['input_search'];
	if(isset($_REQUEST['regioni'])){
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
		$query = " ".$filtri." ";
		$elenco_siti = array();
		$client = new Google_Client();
		$client->setApplicationName("findOnlus");
		$client->setDeveloperKey("AIzaSyDP9hous_IC-Fm9L0EbHug6Pa0Cxs-mf9w");
		
		$search = new Google_Service_Customsearch($client);
		for($i=0;$i<5;$i++){
			$start = ($i*10)+1;
			$result = $search->cse->listCse($query, array(
				'cx' => "002086684897779538086:ojni3tynjbk",'start'=>$start // The custom search engine ID to scope this search query.
			));
			foreach ($result->items as $res){
				array_push($elenco_siti,"http://".$res['formattedUrl']);
			}
		}
		
		esamina($elenco_siti);
	}
?>
	