<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php

	include ("../lib/simple_html_dom.php");
	# include parseCSV class.
	require_once '../lib/parsecsv.lib.php';
	include ("../php/database_manager.php");
	set_time_limit(0);
	
	header('Content-Type: text/html; charset=ISO-8859-1');
	ini_set('default_charset', 'utf-8');
	
	$db = new Db();
	

	$url = "http://www.padovanet.it/noprofit/associazioni_search?search=all";
	$html = file_get_html($url);
	$result = array();
	if(preg_match("/\d{1,} risultati/", $html,$result)){
		foreach ($result as $s)
			echo $s;
	}
	else echo "£asdfihjfoasid";

	$max_value= 1082;
	$number = 5;
	
	$parse = parse_url($url);
	
	$all_link = array();
	while($number <= $max_value){
		$link = $url."&b_start:int=".$number;
		echo "<br>-------------".$link;
		$html = file_get_html($link);
		if(is_object($html)){
			$links = $html->find("#content a");	
			foreach($links as $site){
				if(strpos($site->href,$parse['host']) === false   &&  strpos($site->href,"mailto") === false ) {
					echo $site->href."<br>";
					//array_push($all_link,$site->href);
					$query= "INSERT INTO elenco_siti VALUE('".$site->href."', NULL)";
					$db->query($query);
				}
			}
		}
		$number = $number+5;
	}
	/*
	$url="http://www.nonprofit.viainternet.org/";
	$html = file_get_html($url);
	$link_cat = array();
	if(is_object($html)){
		$categorie = $html->find("ul#listacategorie li a");	
		foreach ($categorie as $c){
			array_push($link_cat,$c);
		}
	}
		
*/
?>