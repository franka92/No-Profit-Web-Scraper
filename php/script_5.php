<?php
	include ("cerca_informazioni.php");
	
	ini_set('default_charset', 'utf-8');
	
	set_time_limit(0);
	$elenco= array();
	$elenco_siti = array();
	$i = (4*$siti_script)+1;
	$query = "SELECT * from elenco_siti WHERE Timestamp is null or TIMESTAMPDIFF(MONTH,Timestamp,now())>1 LIMIT ".$i.",".$siti_script.";";
	$dati = $db -> select($query);
	
	if(count($dati)>0){
		/*Ciclo sui primi n siti*/
		foreach($dati as $row) {
			$link = $row['Sito'];
			$timestamp = $row['Timestamp'];
			
			if ($timestamp == null){/*Il sito non è mai stato analizzato*/ 
				//$site = array();
				$site = findInformation($link);
				if($site != null){
					/*Devo aggiornare il timestamp e inserire i dati*/
					aggiorna_timestamp($link);
					echo "<br>inserisco ".$link;
					inserisci_dati($site);
				}
				else{
					echo "<br>cancello ".$link;
					/*Non ho trovato nulla, cancello il sito dall'elenco*/
					$query = "DELETE FROM elenco_siti WHERE Sito='".$link."';";
					$db->query($query);
				}
			}
			else{

				$site = findInformation($link);
				if($site != null){
					$site_old = recupera_dati($link);
					cancella_vecchie_info($link);
					aggiorna_timestamp($link);
					echo "<br>inserisco ".$link;
					inserisci_dati($site);
				}
				else{
					echo "<br>sito cancellato: ".$link;
				}
			

			}
			
		}
	}
	else{
		echo "errore";
	}
	echo "done";
	
?>