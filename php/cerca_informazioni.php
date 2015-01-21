<?php
	require_once '../vendor/autoload.php';
	require_once ("../lib/simple_html_dom.php");
	# include parseCSV class.
	require_once '../lib/parsecsv.lib.php';
	require_once ("database_manager.php");
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
	
	function stampaElenco($elenco){
		foreach($elenco as $site){
			echo '<div itemscope itemtype="http://schema.org/Organization">';
			echo "<h1 itemprop='name'>".$site['nome']."</h1>";
			echo "<ul>";
			echo "<li>Link: <span itemprop='sameAs'>".$site['link']."</span></li>";
			echo "<li>Categoria: <ul>";
			if(array_key_exists("categoria",$site)){
				foreach ($site['categoria'] as $cat){
					echo "<li><span itemprop='description'>".$cat."</span></li>";
				}
			}
			else{
				echo "<li>Nessuna categoria di riferimento</li>";
			}
			echo "</ul></li>";
			echo "<li>Email:<ul> ";
			if(array_key_exists("email",$site)){
				foreach ($site['email'] as $e){
					echo"<li><span itemprop='email'>".$e."</span></li>";
				}
			}
			else{
				echo "<li>Nessun contatto email</li>";
			}
			echo "</ul></li>";
			echo "<li>Numeri telefonici:<ul> ";
			if(array_key_exists("numero",$site)){
				foreach ($site['numero'] as $n){
					echo"<li><span itemprop='telephone'>".$n."</span></li>";
				}
			}
			else{
				echo "<li>Nessun contatto telefonico</li>";
			}
			echo "</ul></li>";
			echo "<li itemprop='address' itemscope itemtype='http://http://schema.org/PostalAddress'>Citta': ";
			if(array_key_exists("luogo",$site)){
				if(array_key_exists("comune",$site['luogo'])){
					echo '<span itemprop="addressLocality">'.$site['luogo']['comune'].'</span> ('.$site["luogo"]["provincia"].') , ';
					echo '<span itemprop="postalCode">'.$site["luogo"]["cap"].'</span> - ';
					echo '<span itemprop="addressRegion">'.$site["luogo"]["regione"].'</span> ';
					echo '(<span itemprop="addressCountry">IT</span>)';
				}
					//echo $site['luogo']['comune']." (".$site['luogo']['provincia'].") </li>";
			}
			else{
				echo "Impossibile determinare le informazioni relative al luogo</li>";
			}
			echo "</ul>";
		}	
	}
	
	
	/*Funzione che ricerca le informazioni relative al sito*/
	function findInformation($link){
		global $elenco;
		$special_c = array("/[À-Å]/","/Æ/","/Ç/","/[È-Ë]/","/[Ì-Ï]/","/Ð/","/Ñ/","/[Ò-ÖØ]/","/×/","/[Ù-Ü]/","/[Ý-ß]/","/[à-å]/","/æ/","/ç/","/[è-ë]/","/[ì-ï]/","/ð/","/ñ/","/[ò-öø]/","/÷/","/[ù-ü]/","/[ý-ÿ]/");
		$normal_c = array("A","AE","C","E","I","D","N","O","X","U","Y","a","ae","c","e","i","d","n","o","x","u","y");
		if (strpos($link,'http') !== false){
			$dominio = $link;
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
				
				
				$sito['nome'] = substr($link,7,strlen($link));
				foreach($html->find("title") as $element){
					$titolo = $element->plaintext;
					if($titolo != "" && $titolo != "home"){
						/*Elimino eventuali caratteri speciali*/
						$titolo = preg_replace('/s*(home | homepage | home page|index)\s*/',"",$titolo);
						$titolo = preg_replace('/\s{2,}/',' ',$titolo);
						$titolo = preg_replace($special_c,$normal_c, $titolo);
						$titolo = html_entity_decode($titolo);
						$sito['nome'] = trim($titolo," ");
					}
				}
				
				$pag_contatti = $html->find("a[href*=contatti] , a[href*=contact]");
				if(count($pag_contatti) > 0){
					//echo "dentro if <br>";
					foreach($html->find("a[href*=contatti] , a[href*=contact]") as $element){
						$link_contatti = $element->href;
						if(substr($link_contatti,0,strlen($link)) != $dominio){
							if(substr($link_contatti,0,1) == "/"){
								$link_contatti = $dominio . substr($link_contatti,1,strlen($link_contatti));
							}
							else{
								$link_contatti = $dominio . $link_contatti;
							}
						}
						if($link_contatti != ""){
							$sito = findContactInformation($link_contatti,$sito);
						}	
						else{
							echo "<br> Link contatti == null ".$dominio;
						}						
					}
				}
				else{
					foreach($html->find("a") as $element){
						if(strcmp($element->plaintext,"contatti") == 0){
							$link_contatti = $element->href;
							if(substr($link_contatti,0,strlen($link)) != $dominio){
								if(substr($link_contatti,0,1) == "/"){
									$link_contatti = $dominio . substr($link_contatti,1,strlen($link_contatti));
								}
								else{
									$link_contatti = $dominio . $link_contatti;
								}
							}

							if($link_contatti != ""){
								$sito = findContactInformation($link_contatti,$sito);
							}	
							else{
								echo "<br> Link contatti == null ".$dominio;
							}													
						}
						
					}
				}
				if($sito != null){
					if(array_key_exists("email",$sito)){
						//echo "info TROVATE per ".$dominio."<br>";
						array_push($elenco,$sito);
						return $sito;
					}
					else{
						$sito = findContactInformation($link,$sito);
						if($sito != null){
							if(array_key_exists("email",$sito))
								array_push($elenco,$sito);
								return $sito;
						}
						else
							echo "info non trovate per ".$dominio."<br>";
					}
				}

			}
			else{
				echo "Impossibile caricare: ". $dominio."<br>";
				return null;
			}
		}	
		
		return null;
	}
	
	
	
	/*Funzione che ricerca le informazioni di contatto dato un link*/	
	function findContactInformation($link,$sito){
		if(file_get_html($link) === false)
			return null;
		$html = file_get_html($link);
		if ($html == null)
			return null;
		$content = $html->plaintext;
		
		/*Ricerca indirizzi Email*/
		preg_match_all('/([\w+\.]*\w+@[\w+\.]*\w+[\w+\-\w+]*\.\w{2,3})/is',$content,$addresses); 
		$sito['email'] = array();
		foreach($addresses[1] as $curEmail) { 
			$curEmail = preg_replace('/\s{2,}/',' ',$curEmail);
			if(array_search (trim($curEmail," "),$sito['email']) === false){
				array_push($sito['email'],trim($curEmail," "));
			}
		} 
		/*Per le email --> ricerca anche dei link a href="mailto:...."*/
		if(file_get_html($link) != false){
			$html = file_get_html($link);
			foreach($html->find("a[href^=mailto:]") as $element){
				$result = array();
				$email = str_replace("%20","",$element->href);
				preg_match('/([\w+\.]*\w+@[\w+\.]*\w+[\w+\-\w+]*\.\w{2,3})/is',$email,$result); 
				$email = $result[0];
				//$email = trim(substr($element->href,7,strlen($element))," ");
				if(!empty($email) && array_search ($email,$sito['email']) === false)
					array_push($sito['email'],$email);
			}
		}
		/*Ricerca Numeri telefonici*/
		preg_match_all('/\(?\s?\d{3,4}\s?[\)\.\-]?\s*\d{3}\s*[\-\.]?\s*\d{3,4}/',$content,$numbers); 
		$sito['numero'] = array();
		foreach($numbers[0] as $n) { 
			$n = preg_replace('/\s/','',$n);
			if(array_search (trim($n," "),$sito['numero']) === false){
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
		
		/*Ricerca CAP*/
		preg_match_all('/\s\d{2}[01589]\d{2}\s/i',$content,$indirizzi); 
		$sito['luogo'] = array();
		foreach($indirizzi[0] as $ind) { 
			$sito['luogo']['cap'] = $ind;
			$c = new parseCSV();
			$c->delimiter =";";
			$c->parse('../src/listacomuni.csv');
			foreach ($c->data as $key => $row){
				$cap = $row['CAP'];
				if (strpos($cap,'x') != false){
					
					$index = strpos($cap,'x');
					
					$cap_pre = substr($cap,0,$index);
					$ind_pre = substr($ind,0,$index+1);
					if(intval($cap_pre) == intval($ind_pre)){
						$sito['luogo']['comune'] = $row['Comune'];
						$sito['luogo']['provincia'] = $row['Provincia'];
						$sito['luogo']['regione'] = $row['Regione'];
						continue 2;
					}
				}
				else if(intval($cap) == intval($ind)){
					$sito['luogo']['comune'] = $row['Comune'];
					$sito['luogo']['provincia'] = $row['Provincia'];
					$sito['luogo']['regione'] = $row['Regione'];
					continue 2;
				}
			}
		}
		return $sito;

	}
	
	function verifica_timestamp($timestamp){
		if($timestamp == null)
			return true;
			
		$current_time = strtotime("now");
		$last_access_time = strtotime($timestamp);
		
		$time1 = new DateTime();
		$time2 = new DateTime();
	
		$time1->setTimestamp($last_access_time);
		$time2->setTimestamp($current_time);
		$difference = $time2->diff($time1);
		$months = $difference->format("%m");
		if(intval($months) > 0){
			return true;
		}
		else{
			return false;
		}
	}
	
	function recupera_info($link,$file_path){
		global $elenco;
		$json_file = file_get_contents ($file_path);//fopen("results".$i.".json", "r");
		$json_data = json_decode($json_file, true);
		foreach($json_data as $site){
			if(array_key_exists("link",$site)){
				if(strpos($site['link'],$link) !== false){
					array_push($elenco,$site);
					return true;
				}
			}
			else{
				echo "link non esiste: ". $link."<br>";
			}
		}
		return false;
	}
	
	function aggiorna_timestamp($link){
		global $db;
		$query = "UPDATE elenco_siti SET  Timestamp =now() WHERE  Sito =  '".$link."';";
		echo "<br>".$query;
		$db->query($query);
	}
	
	function inserisci_dati($site){
	
		global $db;
		echo "inserisco";
		$categorie = "0";
		$link = $site['link'];
		if(cerca($link) === false){
			$query = "INSERT INTO associazioni VALUE(NULL, ";
			
			$query .= "'".$site['nome'] ."', ";
			$query .= "'".$site['link'] ."', ";
			
			if(array_key_exists("luogo",$site)){
				if(array_key_exists("comune",$site['luogo'])){
					$query .= "'".$site['luogo']['comune'] ."', ";
					$query .= $site['luogo']['cap'] .", ";
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
		
			/********/
			if(array_key_exists("email",$site)){
				foreach ($site['email'] as $e){
					$query = "INSERT INTO elenco_email VALUE(NULL, '".$link."', '".$e."');";
					$db->query($query);
				}

			}

			if(array_key_exists("numero",$site)){
				foreach ($site['numero'] as $n){
					$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
					try {
						$numero = $phoneUtil->parse($n, "IT");
						$num_type = $phoneUtil->getNumberType($numero);
						$query = "INSERT INTO elenco_numeri VALUE(NULL, '".$link."', '".$n."','".$num_type."');";
						$db->query($query);
					} catch (\libphonenumber\NumberParseException $e) {
						//var_dump($e);
					}
				
					/*
					$query = "INSERT INTO elenco_numeri VALUE(NULL, '".$link."', '".$n."');";
					$db->query($query);*/
				}
			}
		}
		else{
			/*do nothing*/
		}
	
	}
	
	function recupera_dati($link){
		global $db;
		$site = array();
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
		$query = "SELECT * FROM elenco_email WHERE sito='".$link."';";
		$result = $db->select($query);
		$site['email'] = array();
		foreach($result as $row){
			$site['email'] = $row['email'];
		}
		$query = "SELECT * FROM elenco_numeri WHERE sito='".$link."';";
		$result = $db->select($query);
		$site['numeri'] = array();
		foreach($result as $row){
			$site['numeri'] = $row['numero'];
		}
		
		
		$query = "SELECT * FROM associazioni_categorie WHERE sito='".$link."';";
		$result = $db->select($query);
		$site['categoria'] = array();
		foreach($result as $row){
			$site['categoria'] = $row['categoria'];
		}
		return $site;
	}
	
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
	
	function cerca($link){
		global $db;
		$query = "SELECT * FROM associazioni WHERE sito='".$link."';";
		$result = $db->select($query);
		if(count($result)>0)
			return true;
			
		return false;
	}
	
	function is_partita_iva($numero){
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
	
	
	

?>