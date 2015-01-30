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
	
	$db = new Db();
	$file_content = file_get_contents("../src/pagina.html");
	$html = new simple_html_dom();
	$html->load($file_content);
	$tutti_div = $html->find("div");
	$count = 0;
	$count_ok = 0;
	$count_no = 0;
	$dir_path = '../data/json';
	
	$elenco_associazioni = array();
	
	foreach($tutti_div as $div){
		if($count > 0) break;
		$titoli =  $div->find("h2");
		foreach($titoli as $t){
			$nome_a = $t->first_child();
			if($nome_a != null){
				$associazione = array();
				$nome = $nome_a->plaintext;
				$nome = preg_replace('/\s{2,}\t/',' ',$nome);
				$associazione['nome'] = $nome;
				$div_anagrafica = $t->next_sibling();
				$div_sito = $div_anagrafica->find("dt.web");
				if(count($div_sito) == 0){
					$associazione = prendi_dati($associazione,$div_anagrafica);
					$associazione['categoria'] = array();
					array_push($associazione['categoria'],"Associazione no profit");
					array_push($elenco_associazioni,$associazione);
					$count++;
				}
				else{
					foreach($div_sito as $d){
						$d_sito = $d->next_sibling();
						$a_sito = $d_sito->first_child()->href;
						$link_parse = parse_url($a_sito,PHP_URL_HOST);
						$res = $db->select("SELECT *from elenco_siti where sito LIKE '%".$link_parse."%';");
						if(count($res) >0){
							$count_ok++;
						}		
						else{
							$associazione['link'] = $a_sito;
							$associazione = prendi_dati($associazione,$div_anagrafica);
							$count_no++;	
							$associazione['categoria'] = array();
							array_push($associazione['categoria'],"Associazione no profit");
							array_push($elenco_associazioni,$associazione);
						}
					}
				
				}
				
			}
			
		}
	}
	echo "Associazioni senza sito: ".$count."<br>";
	echo "Associazioni con sito: ".$count_no."<br>";
	echo "Associazioni con sito gia' presenti: ".$count_ok."<br>";
	
	function prendi_dati($associazione, $div_anagrafica){
		$div_indirizzo = $div_anagrafica->find("dt.indirizzo");
		foreach($div_indirizzo as $d){
			$d_ind = $d->next_sibling();
			$indirizzo = $d_ind->plaintext;	
			$associazione['luogo'] = formatta_indirizzo($indirizzo);
		}
		$div_telefono = $div_anagrafica->find("dt.telefono");
		if(count($div_telefono)>0)
			$associazione['numeri'] = array();
		foreach($div_telefono as $d){
			$d_tel = $d->next_sibling();
			$telefono = $d_tel->plaintext;
			$num = recupera_numeri($telefono);
			if($num != null){
				$associazione['numeri'] = $num;
			}
		}
		$div_email = $div_anagrafica->find("dt.email");
		if(count($div_email)>0)
			$associazione['email'] = array();
		foreach($div_email as $d){
			$d_email = $d->next_sibling()->first_child();
			$email = $d_email->plaintext;	
			array_push($associazione['email'],$email);
		}	
		return $associazione;
	}
	$file_associazioni = fopen($dir_path."/associazioni_no_sito.json", "w");
	$s = json_encode($elenco_associazioni);
		
		fwrite($file_associazioni,utf8_encode($s));
		
		fclose($file_associazioni);
	
	
	
	function recupera_numeri($numeri){
		preg_match_all('/\(?\s?\d{3,4}\s?[\)\.\-]?\s*\d{3}\s*[\-\.]?\s*\d{3,4}/',$numeri,$numbers);
		$num = null;
		if(count($numbers[0]) > 0){
			$num = array();
			foreach($numbers[0] as $n) { 
				$n = preg_replace('/\s/','',$n);
				$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
				try {
					$numero = $phoneUtil->parse($n, "IT");
					$num_type = $phoneUtil->getNumberType($numero);
					$isValid = $phoneUtil->isValidNumber($numero);
					$num_Array = array($n,$num_type);
					if($isValid === true)
						array_push($num,$num_Array);
				} catch (\libphonenumber\NumberParseException $e) {
					//var_dump($e);
				}
			}
		}
		return $num;
	}
	
	
	function formatta_indirizzo($indirizzo){ 
		$ind_exploded = explode(" ",$indirizzo);
		$address = "";
		$cap = null;
		$prov = null;
		$comune = null;
		foreach($ind_exploded as $part){
			if($cap == null){
				preg_match_all('/\d{2}[01589]\d{2},{0,1}/i',$part,$c); 
				if(count($c[0]) >0){
					$cap = $part;
				}
				else{
					$address=$address." ".$part;
				}
			}
			else if($prov == null){
				preg_match_all('/\([A-Z]{2}\)/i',$part,$c); 
				if(count($c[0]) >0){
					$part=str_replace("(","",$part);
					$part=str_replace(")","",$part);
					$prov = $part;
				}
				else{
					$comune=$comune." ".$part;
				}
			}
		}
		$a = array();
		$address = trim(preg_replace('/\s{2,}/',' ',$address)," ");
		$cap = trim(preg_replace('/\s{2,}/',' ',$cap)," ");
		$prov = trim(preg_replace('/\s{2,}/',' ',$prov)," ");
		$comune = trim(preg_replace('/\s{2,}/',' ',$comune)," ");
		
		$address = trim(preg_replace('/[\t\r\n]/',' ',$address)," ");
		$cap = trim(preg_replace('/[\t\r\n]/',' ',$cap)," ");
		$prov = trim(preg_replace('/[\t\r\n]/',' ',$prov)," ");
		$comune = trim(preg_replace('/[\t\r\n]/',' ',$comune)," ");
		
		$a['indirizzo'] = trim(preg_replace('/(&nbsp;)/',' ',$address)," ");
		$a['cap'] = trim(preg_replace('/(&nbsp;)/',' ',$cap)," ");
		$a['provincia'] = trim(preg_replace('/(&nbsp;)/',' ',$prov)," ");
		$a['comune'] = trim(preg_replace('/(&nbsp;)/',' ',$comune)," ");
		
		$a['regione'] = "EMR";
		return $a;
	
	}
	
	
	
	
	
	

?>