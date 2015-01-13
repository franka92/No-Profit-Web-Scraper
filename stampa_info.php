<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php
	include ("/lib/simple_html_dom.php");
	# include parseCSV class.
	require_once '/lib/parsecsv.lib.php';
	
	header('Content-Type: text/html; charset=ISO-8859-1');
	ini_set('default_charset', 'utf-8');
	
	set_time_limit(0);
	$someUA = array (
	"Mozilla/5.0 (Windows; U; Windows NT 6.0; fr; rv:1.9.1b1) Gecko/20081007 Firefox/3.1b1",
	"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.0",
	"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/0.4.154.18 Safari/525.19",
	"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27 Safari/525.13",
	"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)",
	"Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.40607)",
	"Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322)",
	"Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.0.3705; Media Center PC 3.1; Alexa Toolbar; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
	"Mozilla/45.0 (compatible; MSIE 6.0; Windows NT 5.1)",
	"Mozilla/4.08 (compatible; MSIE 6.0; Windows NT 5.1)",
	"Mozilla/4.01 (compatible; MSIE 6.0; Windows NT 5.1)");
	
	$filtri = array(
				array('Arte, Musica, Spettacolo','musica'),
				array('Arte, Musica, Spettacolo','teatro'),
				array('Arte, Musica, Spettacolo','danza'),
				array('Arte, Musica, Spettacolo','arte'),
				array('Arte, Musica, Spettacolo','cinema'),
				array('Arte, Musica, Spettacolo','spettacoli'),
				array('Arte, Musica, Spettacolo','coro')
	);
	
	$elenco = array();
	
	
	/*Recupero i dati dal file .csv*/
	$csv = new parseCSV();
	$csv->auto('src/elenco.csv');

	foreach ($csv->data as $key => $row){
		$link = $row['Sito'];
		$nome = $row['Nome'];
		$info_trovate = false;
		
		$elenco = findInformation($link,$elenco);
		
	}

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
	function findInformation($link,$elenco){
		if (strpos($link,'http') !== false){
			$dominio = $link;
			$result = getContent($link);
			libxml_use_internal_errors(TRUE);
			if($result != null){
				$sito = array();
				$sito['link'] = $link;
				$search_for = array();
				$r = checkForCategory($result, $found = array(), $search_for);
				if ($r != null){
					$sito['categoria'] = array();
				}					
				foreach($r as $content){
					array_push($sito['categoria'],$content);
				}
				$html = file_get_html($link);
				
				if($html != null){
					echo $link."<br>";
					$sito['nome'] = substr($link,10,strlen($link));
					foreach($html->find("title") as $element)
						$sito['nome'] = $element->plaintext;
					$pag_contatti = $html->find("a[href*=contatti] , a[href*=contact]");
					if(count($pag_contatti) > 0){
						echo "dentro if <br>";
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
							echo "info TROVATE per ".$dominio."<br>";
							array_push($elenco,$sito);
						}
						else{
							$sito = findContactInformation($link,$sito);
							if(array_key_exists("email",$sito))
								array_push($elenco,$sito);
							else
								echo "info non trovate per ".$dominio."<br>";
						}
					}
				
				}
				
			}
			else{
				echo "Impossibile caricare: ". $dominio."<br>";
			}
		}	
		
		return $elenco;
	}
	
	
	
	/*Funzione che ricerca le informazioni di contatto dato un link*/	
	function findContactInformation($link,$sito){
		if(file_get_html($link) == false)
			return null;
			
		$content = file_get_html($link)->plaintext;
		
		/*Ricerca indirizzi Email*/
		preg_match_all('/([\w+\.]*\w+@[\w+\.]*\w+[\w+\-\w+]*\.\w+)/is',$content,$addresses); 
		$sito['email'] = array();
		foreach($addresses[1] as $curEmail) { 
			if(array_search ($curEmail,$sito['email']) === false)
				array_push($sito['email'],$curEmail);
		} 
		/*Per le email --> ricerca anche dei link a href="mailto:...."*/
		if(file_get_html($link) != false){
			foreach(file_get_html($link)->find("a[href*=mailto]") as $element){
				if(array_search (substr($element->href,7,strlen($element)),$sito['email']) === false)
					array_push($sito['email'],substr($element->href,7,strlen($element)));
			}
		}
		/*Ricerca Numeri telefonici*/
		preg_match_all('/\(?\s?\d{3,4}\s?[\)\.\-]?\s*\d{3}\s*[\-\.]?\s*\d{3,4}/',$content,$numbers); 
		$sito['numero'] = array();
		foreach($numbers[0] as $n) { 
			if(array_search ($n,$sito['numero']) === false)
				array_push($sito['numero'],$n);
		}
		
		/*Ricerca CAP*/
		preg_match_all('/\s\d{2}[01589]\d{2}\s/i',$content,$indirizzi); 
		$sito['luogo'] = array();
		foreach($indirizzi[0] as $ind) { 
			$sito['luogo']['cap'] = $ind;
			$c = new parseCSV();
			$c->delimiter =";";
			$c->parse('src/listacomuni.csv');
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
				array('Arte, Musica, Spettacolo','musica'),
				array('Arte, Musica, Spettacolo','teatro'),
				array('Arte, Musica, Spettacolo','danza'),
				array('Arte, Musica, Spettacolo','arte'),
				array('Arte, Musica, Spettacolo','cinema'),
				array('Arte, Musica, Spettacolo','spettacoli'),
				array('Arte, Musica, Spettacolo','coro'),
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

function getRandomUserAgent ( ) {
    //srand((double)microtime()*1000000);
    global $someUA;
    return $someUA[rand(0,count($someUA)-1)];
}
function getContent ($url) {
 
    // Crea la risorsa CURL
    $ch = curl_init();
 
    // Imposta l'URL e altre opzioni
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, getRandomUserAgent());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    // Scarica l'URL e lo passa al browser
    $output = curl_exec($ch);
    $info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // Chiude la risorsa curl
    curl_close($ch);
    if ($output === false || $info != 200) {
      $output = null;
    }
    return $output;
 
}
?>