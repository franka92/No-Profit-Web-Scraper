<?php
	require '../vendor/autoload.php';
	include ("../lib/simple_html_dom.php");
	# include parseCSV class.
	require_once '../lib/parsecsv.lib.php';
	include ("database_manager.php");
	
	ini_set('default_charset', 'utf-8');	
	set_time_limit(0);
		echo "<br>********* TEMPO INIZIALE".date('m/d/Y H:i:s', time());
		$tempo_iniziale = time();
	$filtri = array(
				array('Arte, Musica, Spettacolo','musica'),
				array('Arte, Musica, Spettacolo','teatro'),
				array('Arte, Musica, Spettacolo','danza'),
				array('Arte, Musica, Spettacolo','arte'),
				array('Arte, Musica, Spettacolo','cinema'),
				array('Arte, Musica, Spettacolo','spettacoli'),
				array('Arte, Musica, Spettacolo','coro')
	);

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
	echo "<br>DONE";
	
	}
	
	
	/*Funzione che ricerca le informazioni relative al sito*/
	function findInformation($link){
		global $elenco;
		if (strpos($link,'http') !== false){
			$dominio = $link;
			$html = file_get_html($link);
			if($html != null){
				$sito = array();
				$sito['link'] = $link;
				$search_for = array();
				$r = checkForCategory($html, $found = array(), $search_for);
				if ($r != null){
					$sito['categoria'] = array();
					foreach($r as $content){
						array_push($sito['categoria'],$content);
					}
				}					
				$sito['nome'] = substr($link,7,strlen($link));
				foreach($html->find("title") as $element){
					$titolo = $element->plaintext;
					if($titolo != ""){
						$titolo = preg_replace('/\s{2,}/',' ',$titolo);
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
		preg_match_all('/([\w+\.]*\w+@[\w+\.]*\w+[\w+\-\w+]*\.\w+)/is',$content,$addresses); 
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
				preg_match('/([\w+\.]*\w+@[\w+\.]*\w+[\w+\-\w+]*\.\w+)/is',$email,$result); 
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
			$n = preg_replace('/\s{2,}/',' ',$n);
			if(array_search (trim($n," "),$sito['numero']) === false){
				$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
				try {
					$numero = $phoneUtil->parse($n, "IT");
				} catch (\libphonenumber\NumberParseException $e) {
					//var_dump($e);
				}
				$isValid = $phoneUtil->isValidNumber($swissNumberProto);
				if($isValid === true && is_partita_iva($n) === false)
					array_push($sito['numero'],trim($n," "));
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
	

	/*Cerca i termini nella pagina*/
	function checkForCategory($page, $found = array(), $filter = array()){
		$filtri = array(
				array('Arte Musica Spettacolo','musica'),
				array('Arte Musica Spettacolo','teatro'),
				array('Arte Musica Spettacolo','danza'),
				array('Arte Musica Spettacolo','arte'),
				array('Arte Musica Spettacolo','cinema'),
				array('Arte Musica Spettacolo','spettacoli'),
				array('Arte Musica Spettacolo','coro'),
				array('Sport','sport'),
				array('Sport','calcio'),
				array('Sport','pallacanestro'),
				array('Sport','basket'),
				array('Sport','centro velico')
		);
		$filter = ((!empty($filter) && is_array($filter)) ? $filter : $filtri);
		$found = is_array($found) ? $found : array();
		foreach($filtri  as $test){
			if(in_array($test[0], $found)) continue;
			if(preg_match("/".$test[1]."/i", $page)){
				array_push($found,$test[0]);
			}
		}
		return $found;
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
					$query .= "'".$site['luogo']['regione'] ."', ";
				}
				else{
					$query .= "NULL, NULL, NULL, NULL, ";
				}
			}
			else{
				$query .= "NULL, NULL, NULL, NULL, ";
			}
			
			if(array_key_exists("categoria",$site)){
				global $elenco_categorie;
				foreach ($site['categoria'] as $cat){
					foreach ($elenco_categorie as $e_c){
						if (strcmp($e_c['nome'],$cat) == 0){
							if ($categorie == "00")
								$categorie = $e_c['codice_categoria'];
							else
								$categorie .= "-".$e_c['codice_categoria'];
						}
					}
				}
			}
			$query .= "'".$categorie;
			
			$query .= "');";
			echo $query;
			$db->query($query);
			
			/********/
			if(array_key_exists("email",$site)){
				foreach ($site['email'] as $e){
					$query = "INSERT INTO elenco_email VALUE(NULL, '".$link."', '".$e."');";
					$db->query($query);
				}
			}

			if(array_key_exists("numero",$site)){
				foreach ($site['numero'] as $n){
					$query = "INSERT INTO elenco_numeri VALUE(NULL, '".$link."', '".$n."');";
					$db->query($query);
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
		if(strlen($n) == 11 && strrpos($n," ") === false){
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