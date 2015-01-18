<?php
	include ("cerca_informazioni.php");
	
	ini_set('default_charset', 'utf-8');
	
	set_time_limit(0);
	$elenco= array();
	$elenco_siti = array();
	$query = "SELECT * from elenco_siti WHERE Timestamp is null or TIMESTAMPDIFF(MONTH,Timestamp,now())>1 LIMIT ".($siti_script+1).";";
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
				/*Cerco le informazioni solo se è passato più di un mese dall'ultimo controllo*/
				if(verifica_timestamp($timestamp) === true){
				
					$site = findInformation($link);
					if($site != null){
						/*Devo recuperare le informazioni attuali sul database associate al sito
							Confronto le informazioni trovate, con quelle precedenti
							Se non ci sono differenze, aggiorno solo il timestamp
							Altrimenti elimino i dati relativi al sito dal database e carico i nuovi
							Segnalo su un report che ho modificato i 
						*/
						$site_old = recupera_dati($link);
						
						/*foreach($y=0;$y<count($site);$y++){
							$difference = array_diff($site[$y],$site_old[$y]);
							if($difference != null)
								echo "<br>ci sono cambiamenti per: ".key($site[$y]);
						}*/
						cancella_vecchie_info($link);
						aggiorna_timestamp($link);
						echo "<br>inserisco ".$link;
						inserisci_dati($site);

						echo "no<br>";
					}
					else{
						echo "<br>sito cancellato: ".$link;
					}
				}
				else{/*Altrimenti mantengo le informazioni precedenti --> non faccio nulla*/
					
				}
			}
			
		}
	}
	else{
		echo "errore";
	}
	echo "done";
	
?>