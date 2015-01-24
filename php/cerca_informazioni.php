<?php
	require_once '../vendor/autoload.php';
	# include Simple Html Dom lib
	require_once ("../lib/simple_html_dom.php");
	# include parseCSV class.
	require_once '../lib/parsecsv.lib.php';
	# include Database Configuration and management class
	require_once ("database_manager.php");
	# include find category class
	require_once ("trova_categorie.php");
	
	ini_set('default_charset', 'utf-8');	
	set_time_limit(0);

	$numero_script = 5;
	$db = new Db();
	$query = "SELECT * from elenco_siti WHERE Timestamp is null or TIMESTAMPDIFF(MONTH,Timestamp,now())>1";
	$result = $db -> select($query);
	if(count($result)>0){
		$numero_siti = count($result);
	} 
	else{
		$numero_siti = 0;
	}
	$siti_script = round($numero_siti/$numero_script);
	
	
	/*Recupero dal database tutte le categorie*/
	$query = "SELECT * FROM categorie";
	$elenco_categorie = $db->select($query);
	
	
	/*Funzione che ricerca le informazioni relative al sito
	  @param link: url del sito da analizzare
	  @return: array contenente le informazioni trovate (null altrimenti)
	*/
	function findInformation($link){
		global $elenco;
		$special_c = array("/[À-Å]/","/Æ/","/Ç/","/[È-Ë]/","/[Ì-Ï]/","/Ð/","/Ñ/","/[Ò-ÖØ]/","/×/","/[Ù-Ü]/","/[Ý-ß]/","/[à-å]/","/æ/","/ç/","/[è-ë]/","/[ì-ï]/","/ð/","/ñ/","/[ò-öø]/","/÷/","/[ù-ü]/","/[ý-ÿ]/");
		$normal_c = array("A","AE","C","E","I","D","N","O","X","U","Y","a","ae","c","e","i","d","n","o","x","u","y");
		if (strpos($link,'http') !== false){
			$dominio = $link;
			/*Recupero il contenuto del sito*/
			$html = file_get_html($link);
			if($html != null){
				$sito = array();
				$sito['link'] = $link;
				/*Cerco una categoria*/
				$categorie = trova_categorie($link);
				if($categorie != null){
					$sito['categoria'] = array();
					$value = max($categorie);
					$key = array_keys($categorie,$value);
					foreach($key as $c)
						array_push($sito['categoria'],$c);
				}
				
				/*Cerco il nome del sito/associazione*/
				$sito['nome'] = parse_url($link,PHP_URL_HOST);
				foreach($html->find("title") as $element){
					$titolo = $element->plaintext;
					if($titolo != "" && $titolo != "home"){
						/*Elimino eventuali caratteri speciali*/
						$titolo = preg_replace('/s*(home | homepage | home page|index)\s*/',"",$titolo);
						$titolo = preg_replace('/\s{2,}/',' ',$titolo);
						$titolo = preg_replace("/'/","",$titolo);
						$titolo = preg_replace($special_c,$normal_c, $titolo);
						$titolo = html_entity_decode($titolo);
						$sito['nome'] = trim($titolo," ");
					}
				}
				
				/*Cerco i link alla pagina "Contatti", in cui di solito sono riportate le informazioni*/
				$pag_contatti = $html->find("a[href*=contatt] , a[href*=contact], a[href*=dove], a[href*=siamo], a[href*=sede]");
				if(count($pag_contatti) > 0){
					//echo "dentro if <br>";
					foreach($pag_contatti as $element){
						$link_contatti = $element->href;
						/*Trasformo il link relativo in assoluto*/
						/*if(substr($link_contatti,0,strlen($link)) != $dominio){
							if(substr($link_contatti,0,1) == "/" && substr($dominio,strlen($dominio)-1,strlen($dominio)) == "/"){
							echo $link_contatti."<br>";
								$link_contatti = $dominio . substr($link_contatti,1,strlen($link_contatti));
								echo $link_contatti."<br>";
							}
							else if(substr($link_contatti,0,1) != "/" && substr($dominio,strlen($dominio)-1,strlen($dominio)) != "/"){
								$link_contatti = $dominio . "/".$link_contatti;
							}
							else{
								$link_contatti = $dominio .$link_contatti;
							}
						}*/
						$link_contatti = get_absolute_url($link_contatti,$dominio);
						/*Richiamo la funzione che ricerca le informazioni di contatto*/
						if($link_contatti != ""){
							$sito = findContactInformation($link_contatti,$sito);
						}						
					}
				}
				/*Se non ho trovato il link alla pagina "Contatti", provo una ricerca più approfondita su tutti gli anchor-node*/
				else{
					foreach($html->find("a") as $element){
						if(strpos(strtolower($element->plaintext),"contatt") !== false || strpos(strtolower($element->plaintext),"dove")|| strpos(strtolower($element->plaintext),"siamo") || strpos(strtolower($element->plaintext),"sede")){
							$link_contatti = $element->href;
							$link_contatti = get_absolute_url($link_contatti,$dominio);
							/*Richiamo la funzione che ricerca le informazioni di contatto*/
							if($link_contatti != ""){
								$sito = findContactInformation($link_contatti,$sito);
							}													
						}
						
					}
				}
				/*Ho trovato le informazioni di contatto*/
				if(array_key_exists("email",$sito) && array_key_exists("luogo",$sito)){
					//array_push($elenco,$sito);
					return $sito;
				}
				/*Se le informazioni non sono state trovate, provo un'ultima ricerca direttamente nell'homepage*/
				else{
					$sito = findContactInformation($link,$sito);
					return $sito;

				}
				return $sito;
			}
			/*Non è stato possibile caricare la pagina*/
			else{
				echo "Impossibile caricare: ". $dominio."<br>";
				return null;
			}
		}	
		
		return null;
	}
	
	
	
	/*Funzione che ricerca le informazioni di contatto dato un link
		@param link: link della pagina su cui effettuare la ricerca
		@param $sito: array contenente le informazioni
		
		@return: array con le informazioni trovate
	*/	
	function findContactInformation($link,$sito){
		/*Se non riesco ad aprire il link, termino*/
		if(file_get_html($link) === false)
			return $sito;
		$html = file_get_html($link);
		if ($html == null)
			return $sito;
			
		/*Recupero il contenuto della pagina*/
		$content = $html->plaintext;
		
		/*Ricerca indirizzi Email*/
		if(array_key_exists("email",$sito) === false ){
			preg_match_all('/([\w+\.]*\w+@[\w+\.]*\w+[\w+\-\w+]*\.\w{2,3})/is',$content,$addresses); 
			if(count($addresses[0]) > 0){
				$sito['email'] = array();
				foreach($addresses[1] as $curEmail) { 
					$curEmail = preg_replace('/\s{2,}/',' ',$curEmail);
					if(array_search (trim($curEmail," "),$sito['email']) === false){
						array_push($sito['email'],trim($curEmail," "));
					}
				} 
			}
			else{
				/*Per le email --> ricerca anche dei link a href="mailto:...."*/
				foreach($html->find("a[href^=mailto:]") as $element){
					$result = array();
					$email = str_replace("%20","",$element->href);
					preg_match('/([\w+\.]*\w+@[\w+\.]*\w+[\w+\-\w+]*\.\w{2,3})/is',$email,$result); 
					$email = $result[0];
					if(!empty($email)){
						if(array_key_exists("email",$sito) === false )
							$sito['email'] = array();
						if(array_search ($email,$sito['email']) === false)
							array_push($sito['email'],$email);
					}
					
				}
			}
		
		}
		
		/*Ricerca Numeri telefonici*/
		if(array_key_exists("numero",$sito) === false ){
			preg_match_all('/\(?\s?\d{3,4}\s?[\)\.\-]?\s*\d{3}\s*[\-\.]?\s*\d{3,4}/',$content,$numbers); 
			if(count($numbers[0]) > 0){
				$sito['numero'] = array();
				foreach($numbers[0] as $n) { 
					$n = preg_replace('/\s/','',$n);
					if(array_search (trim($n," "),$sito['numero']) === false){
						/*Utilizzo la libreria "libphonenumber" per recuperare informazioni sul numero*/
						$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
						try {
							$numero = $phoneUtil->parse($n, "IT");
							$isValid = $phoneUtil->isValidNumber($numero);
							if($isValid === true && is_partita_iva($n) === false)
								array_push($sito['numero'],trim($n," "));
						} catch (\libphonenumber\NumberParseException $e) {
							//var_dump($e);
						}
						
					}
				}
			}
		}
		/*Ricerca CAP*/
		if(array_key_exists("luogo",$sito) === false ){
			preg_match_all('/\s\d{2}[01589]\d{2},{0,1}\s/i',$content,$indirizzi); 
			if(count($indirizzi[0]) > 0){
				$sito['luogo'] = array();
				foreach($indirizzi[0] as $ind) { 
					$sito['luogo']['cap'] = preg_replace('/\s/','',$ind);
					$c = new parseCSV();
					$c->delimiter =";";
					$c->parse('../src/listacomuni.csv');
					foreach ($c->data as $key => $row){
						$cap = $row['CAP'];
						if (strpos($cap,'x') != false){
							
							$index = strpos($cap,'x');
							
							$cap_pre = substr($cap,0,$index);
							$ind_pre = substr($ind,0,$index+1);
							if(intval($cap_pre) == intval($ind_pre) && $row['Provincia'] == "BO"){
								$sito['luogo']['comune'] = $row['Comune'];
								$sito['luogo']['provincia'] = $row['Provincia'];
								$sito['luogo']['regione'] = $row['Regione'];
								continue 2;
							}
						}
						else if(intval($cap) == intval($ind) && $row['Provincia'] == "BO"){
							$sito['luogo']['comune'] = $row['Comune'];
							$sito['luogo']['provincia'] = $row['Provincia'];
							$sito['luogo']['regione'] = $row['Regione'];
							continue 2;
						}
					}
				}
			}
		}
		return $sito;

	}

	/*Aggiorna il timestamp
		@param link: entry da aggiornare
	*/
	function aggiorna_timestamp($link){
		global $db;
		$query = "UPDATE elenco_siti SET  Timestamp =now() WHERE  Sito =  '".$link."';";
		echo "<br>".$query;
		$db->query($query);
	}
	
	/*Inserisce i dati sul database
		@param site: array con i dati da inserire
	*/
	function inserisci_dati($site){
		global $db;
		$categorie = "";
		$link = $site['link'];
		/*Inserisco solo se i dati non sono già presenti*/
		if(cerca($link) === false){
			$query = "INSERT INTO associazioni VALUE(NULL, ";
			
			$query .= "'".$site['nome'] ."', ";
			$query .= "'".$site['link'] ."', ";
			
			if(array_key_exists("luogo",$site)){
				if(array_key_exists("comune",$site['luogo'])){
					$query .= "'".$site['luogo']['comune'] ."', ";
					$query .= "'".$site['luogo']['cap'] ."', ";
					$query .= "'".$site['luogo']['provincia'] ."', ";
					$query .= "'".$site['luogo']['regione'] ."'";
				}
				else{
					$query .= "NULL, NULL, NULL, NULL ";
				}
			}
			else{
				$query .= "NULL, NULL, NULL, NULL ";
			}
			$query .= ");";
			echo $query."<br>";
			$db->query($query);
			
			/*** categoria ***/
			if(array_key_exists("categoria",$site)){
				foreach ($site['categoria'] as $cat){
					$query = "INSERT INTO associazioni_categorie VALUE('".$link."', '".$cat."');";
					$db->query($query);
				}
			}
		
			/*** Email ***/
			if(array_key_exists("email",$site)){
				foreach ($site['email'] as $e){
					$query = "INSERT INTO elenco_email VALUE('".$link."', '".$e."');";
					$db->query($query);
				}

			}
			/*** Numeri Telefono ***/
			if(array_key_exists("numero",$site)){
				foreach ($site['numero'] as $n){
					$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
					try {
						$numero = $phoneUtil->parse($n, "IT");
						$num_type = $phoneUtil->getNumberType($numero);
						$query = "INSERT INTO elenco_numeri VALUE('".$link."', '".$n."','".$num_type."');";
						$db->query($query);
					} catch (\libphonenumber\NumberParseException $e) {
						//var_dump($e);
					}
				}
			}
		}
		else{
			/*do nothing*/
		}
	
	}
	
	/*Recupera i dati di una certa associazione
		@param link: link dell'associazione in questione
	*/
	function recupera_dati($link){
		global $db;
		$site = array();
		/*Recupero le informazioni di base*/
		$query = "SELECT * FROM associazioni WHERE sito='".$link."';";
		$result = $db->select($query);
		foreach($result as $row){
			$site['nome'] = $row['nome associazione'];
			$site['link'] = $row['sito'];
			$site['luogo'] = array();
			$site['luogo']['comune'] = $row['comune'];
			$site['luogo']['cap'] = $row['cap'];
			$site['luogo']['provincia'] = $row['regione'];
			$site['luogo']['regione'] = $row['provincia'];
		}
		/*Recupero i contatti email*/
		$query = "SELECT * FROM elenco_email WHERE sito='".$link."';";
		$result = $db->select($query);
		$site['email'] = array();
		foreach($result as $row){
			$site['email'] = $row['email'];
		}
		/*Recupero i contatti telefonici*/
		$query = "SELECT * FROM elenco_numeri WHERE sito='".$link."';";
		$result = $db->select($query);
		$site['numeri'] = array();
		foreach($result as $row){
			$site['numeri'] = $row['numero'];
		}
		/*Recupero la categoria*/
		$query = "SELECT * FROM associazioni_categorie WHERE sito='".$link."';";
		$result = $db->select($query);
		$site['categoria'] = array();
		foreach($result as $row){
			$site['categoria'] = $row['categoria'];
		}
		return $site;
	}
	
	/*Cancella tutte le informazioni associate ad un sito
		@param link: link dell'associazione di cui si vogliono cancellare le info
	*/
	function cancella_vecchie_info($link){
		global $db;
		$site = array();
		$query = "DELETE FROM associazioni WHERE sito='".$link."';";
		$result = $db->query($query);
		
		$query = "DELETE FROM elenco_email WHERE sito='".$link."';";
		$result = $db->query($query);
		$query = "DELETE FROM elenco_numeri WHERE sito='".$link."';";
		$result = $db->query($query);

		
	
	}
	
	/*Ricerca un'associazione sul database
		@param link: link dell'associazione da cercare
	*/
	function cerca($link){
		global $db;
		$query = "SELECT * FROM associazioni WHERE sito='".$link."';";
		$result = $db->select($query);
		if(count($result)>0)
			return true;
			
		return false;
	}
	
	/*Verfica se un numero corrisponde ad una Partita Iva
		@param numero: numero da controllare
	*/
	function is_partita_iva($numero){
		/*La funzione è stata costruita in base all'algoritmo di verifica di una P.Iva
			http://it.wikipedia.org/wiki/Partita_IVA
		*/
		if(strlen($numero) == 11 && strrpos($numero," ") === false){
			$x = 0;
			$y = 0;
			$array_num = str_split($numero);
			$car_controllo = intval($array_num[count($array_num)-1]);
			echo "controllo: ".$car_controllo;
			for($i=0;$i<9;$i=($i+2)){
				$x = $x+intval($array_num[$i]);
			}
			
			for($i=1;$i<11;$i=($i+2)){
				$mol = intval($array_num[$i])*2;
				if($mol >9)
					$mol = $mol-9;
				$y = $y+$mol;
			}

			$result = ($x+$y)%10;
			if($car_controllo == 0 && $result == 0)
				return true;
			else if ($car_controllo != 0){
				$result = 10-$result;
				if($car_controllo == $result)
					return true;
			}
			return false;
		
		}
		return false;
	}
	
	
	function aggiorna_dati($link,$info_nuove,$info_vecchie){
		global $db;
		/*Verifica delle informazioni sul luogo*/
		if(array_key_exists("luogo",$info_nuove) && array_key_exists("luogo",$info_vecchie)){
			$luogo_nuovo = $info_nuove['luogo'];
			$luogo_vecchio = $info_vecchio['luogo'];
			/*Se le informazioni sul luogo sono cambiate, le aggiorno*/
			if(array_key_exists("cap",$luogo_nuovo)){
				if(array_key_exists("cap",$luogo_vecchio) && $luogo_nuovo['cap'] != $luogo_vecchio['cap']){
					$db->query("Update associazioni set comune='".$luogo_nuovo['comune']."' where sito='".$link."';");
					$db->query("Update associazioni set cap='".$luogo_nuovo['cap']."' where sito='".$link."';");
					$db->query("Update associazioni set regione='".$luogo_nuovo['regione']."' where sito='".$link."';");
					$db->query("Update associazioni set provincia='".$luogo_nuovo['provincia']."' where sito='".$link."';");
				}
			}
		}
		else if(array_key_exists("luogo",$info_nuove)){
			$luogo_nuovo = $info_nuove['luogo'];
			$db->query("Update associazioni set comune='".$luogo_nuovo['comune']."' where sito='".$link."';");
			$db->query("Update associazioni set cap='".$luogo_nuovo['cap']."' where sito='".$link."';");
			$db->query("Update associazioni set regione='".$luogo_nuovo['regione']."' where sito='".$link."';");
			$db->query("Update associazioni set provincia='".$luogo_nuovo['provincia']."' where sito='".$link."';");
		}
		
		/*Verifica delle informazioni sull'email*/
		if(array_key_exists("email",$info_nuove)){
			$email = $info_nuove['email'];
			if(array_key_exists("email",$info_vecchie)){
				foreach($info_vecchie['email'] as $e){
					if(array_search($email,$e) === false){
						$db->query("DELETE FROM elenco_email where email='".$e."';");
					}
				}	
			}
			foreach($email as $e){
				if(array_search($info_vecchie['email'],$e) === false){
					$db->query("INSERT INTO elenco_email VALUE('".$link."', '".$e."');");
				}
				
			}

		}
		
		/*Verifica delle informazioni sui numeri telefonici*/
		if(array_key_exists("numeri",$info_nuove)){
			$numeri = $info_nuove['numeri'];
			if(array_key_exists("numeri",$info_vecchie)){
				foreach($info_vecchie['numeri'] as $n){
					if(array_search($numeri,$n) === false){
						$db->query("DELETE FROM elenco_numeri where numero='".$n['numero']."';");
					}
				}	
			}
			foreach($numeri as $n){
				if(array_search($info_vecchie['numeri'],$n) === false){
					$db->query("INSERT INTO elenco_email VALUE('".$link."', '".$n['numero']."');");
				}
				
			}

		}
	
	
	}
	
	

?>