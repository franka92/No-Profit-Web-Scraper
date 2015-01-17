<?php
	require_once '../lib/parsecsv.lib.php';

	if (!file_exists('../data/esecuzione_'.time())) {
		$dir_path = '../data/esecuzione_'.time();
		mkdir($dir_path, 0777, true);
		$file_associazioni = fopen($dir_path."/associazioni_".time().".csv", "w");
		$file_email = fopen($dir_path."/elenco-email_".time().".csv", "w");
		$file_numeri = fopen($dir_path."/elenco-numeri_".time().".csv", "w");
		
	}
	else{
		echo "impossibile creare la cartella";
	}
	fputcsv($file_associazioni,explode(",","nome associazione,sito,comune,cap,provincia,regione,categoria"));
	fputcsv($file_email,explode(",","sito associazione,email"));
	fputcsv($file_numeri,explode(",","sito associazione,telefono"));
	/*Apro il file relativo alle categorie*/
	$csv_categorie = new parseCSV();
	$csv_categorie->auto('../src/elenco_categorie.csv');
	$elenco_categorie = $csv_categorie->data;
	

	unlink ("../src/elenco.csv");
	$file_elenco = fopen("../src/elenco.csv", "w");
	fputcsv($file_elenco,explode(",","Sito,Timestamp"));
	
	for ($i=1;$i<=5;$i++){
		$file_path = "../data/results".$i.".json";
		$file_path2 = "../src/elenco".$i.".json";
		scrivi_file($file_path,0);
		scrivi_file($file_path2,1);
	}
	
	function scrivi_file($file_path,$type){ 
		//for ($i=1;$i<3;$i++){
		if($type == 0){
			echo "for";
			//$file_path = "../data/results".$i.".json";
			$json_file = file_get_contents ($file_path);//fopen("results".$i.".json", "r");
			$json_data = json_decode($json_file, true);
			foreach($json_data as $site){
				if(array_key_exists("link",$site)){
					//echo "<br>STO SCRIVENDO: ".$site['link'];
					$nome = preg_replace('/ {2,}/',' ',$site['nome']);
					$link = $site['link'];
					$comune = null;
					$provincia = null;
					$regione = null;
					$cap = null;
					$timestamp;
					$categorie = "";
					

					global $elenco_categorie;
					global $file_associazioni;
					global $file_email;
					global $file_numeri;
					
					global $db;
					
					if(array_key_exists("categoria",$site)){
						if(count($site['categoria']) > 0){
							foreach ($site['categoria'] as $cat){
								foreach($elenco_categorie as $e_c){
									if(strcmp($cat,$e_c['nome']) == 0){
										if($categorie == "")
											$categorie .= $e_c['codice categoria'];
										else
											$categorie .= "-".$e_c['codice categoria'];
									}
								}
								
							}
						}
						else{
							$categorie = "00";
						}
					}
					if(array_key_exists("luogo",$site)){
						if(array_key_exists("cap",$site['luogo']))
							$cap = $site["luogo"]["cap"];
						if(array_key_exists("comune",$site['luogo'])){
							$comune = $site['luogo']['comune'];
							$provincia = $site["luogo"]["provincia"];
							$regione = $site["luogo"]["regione"];
						}
					}
					fputcsv($file_associazioni, array('nome associazione' => $nome, 'sito' => $link, 'comune' => $comune, 'cap' => $cap,
														'provincia' => $provincia,'regione' => $regione, 'categoria' => $categorie));
					
					
					$query = "INSERT INTO `web-scraper`.`associazioni` (`cod_associazione`, `nome associazione`, `sito`, `comune`, `cap`, `provincia`, `regione`, `categoria`) VALUES (NULL, '".$nome."', '".$link."', '".$comune."', '".$cap."',  '".$provincia."',  '".$regione."',  '".$categorie."');";
					
					/*$query = "INSERT INTO  'associazioni' (
								nome associazione ,sito ,comune ,cap ,provincia ,regione ,categoria)
								VALUES (
								'".$nome."',  '".$link."',  '".$comune."',  '".$cap."',  '".$provincia."',  '".$regione."',  '".$categorie."'
								);";*/
					//echo $query;
					//$result = $db->query($query);
					//echo $result;
					
					if(array_key_exists("email",$site)){
						foreach ($site['email'] as $e){
							fputcsv($file_email, array('sito associazione' => $link, 'email' => $e));
							$query = "INSERT INTO  'web-scraper.elenco_email' (sito ,email)
										VALUES ('".$link."',  '".$e."');";
							//$result = $db->query($query);
						}
					}
					if(array_key_exists("numero",$site)){
						foreach ($site['numero'] as $n){
							$n = preg_replace('/ {2,}/','',$n);
							fputcsv($file_numeri, array('sito associazione' => $link, 'numero' => preg_replace('/ {2,}/','',$n)));
							$query = "INSERT INTO  'web-scraper.elenco_numeri' (sito ,numero)
										VALUES ('".$link."',  '".$n."');";
							//$result = $db->query($query);
						}

					}
				}
			
			}
		//}
		}
		else{
			global $file_elenco;
			$json_file = file_get_contents ($file_path);//fopen("results".$i.".json", "r");
			$json_data = json_decode($json_file, true);
			foreach($json_data as $site){
				fputcsv($file_elenco, array('Sito' => $site['Sito'], 'Timestamp' => $site['Timestamp']));
			}
		}
	}
		fclose($file_associazioni);
		fclose($file_email);
		fclose($file_numeri);
		fclose($file_elenco);	

	
	
	
	
?>