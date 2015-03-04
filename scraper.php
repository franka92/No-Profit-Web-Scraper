<?php
	include ("php/cerca_informazioni.php");
	include ("php/crea_dataset_csv.php");
	include ("php/crea_dataset_json.php");
	include ("php/crea_dataset_rdf.php");
	ini_set('default_charset', 'utf-8');
	
	set_time_limit(0);
	$elenco= array();
	$elenco_siti = array();
	$query = "SELECT * from elenco_siti WHERE Timestamp is null or TIMESTAMPDIFF(MONTH,Timestamp,now())>0;";
	$dati = $db -> select($query);
	
	if(count($dati)>0){
		/*Ciclo sui primi n siti*/
		foreach($dati as $row) {
			$link = $row['Sito'];
			$timestamp = $row['Timestamp'];
			
			if ($timestamp == null){/*Il sito non è mai stato analizzato*/ 
				$site = findInformation($link);
				if($site != null){
					/*Devo aggiornare il timestamp e inserire i dati*/
					aggiorna_timestamp($link);
					echo "\nAnalizzato: ".$link;
					inserisci_dati($site);
				}
				else{
					echo "\nImpossibile analizzare. Cancellato ".$link;
					/*Non ho trovato nulla, cancello il sito dall'elenco*/
					$query = "DELETE FROM elenco_siti WHERE Sito='".$link."';";
					$db->query($query);
				}
			}
			else{
				$site = findInformation($link);
				if($site != null){
					$site_old = recupera_dati($link);
					aggiorna_dati($link,$site,$site_old);
					aggiorna_timestamp($link);
					echo "\nAnalizzato: ".$link;
				}
				/*Se non riesco più a recuperare il sito, significa che non è più online e cancello le info*/
				else{
					cancella_vecchie_info($link);
					echo "\nImpossibile analizzare. Cancellato ".$link;
				}

			}
			
		}
		crea_data_csv();
		crea_data_json();
		crea_data_rdf();
		echo "Analisi Completata. Dati inseriti sul Database. Creati file dataset";
	}
	else{
	crea_data_csv();
		crea_data_json();
		crea_data_rdf();
		echo "Non ci sono dati da analizzare.";
	}

	
?>