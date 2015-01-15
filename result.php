<?php
	//include "file.php";
	header('Content-Type: text/html; charset=ISO-8859-1');
	//error_reporting(E_ALL ^ E_NOTICE);
	
	$regioni = $_REQUEST['regioni'];
	$province = $_REQUEST['province'];
	$filtri = "";
	$query = $_POST['input_search'];
	if(isset($_REQUEST['regioni'])){
		for($i=0;$i<count($regioni);$i++){
			if($i >0)
				$filtri .= " OR ";
			$found = false;
			$reg = explode("_", $regioni[$i]);
			if(isset($_REQUEST['province'])){
				for($z=0;$z<count($province);$z++){
					$prov = explode("_", $province[$z]);
					if($prov[1] == $reg[1]){
						if($found == true){
							$filtri.= " OR ";
						}
						$filtri .= $query." ".$prov[0];
						$found = true;
					}
				}
			}
			if(!$found)
				$filtri .= $query." ".$reg[0];
		}
		$query = " ".$filtri." ";
		echo $query;
	}
?>
	

<html>
  <head>
    <title>JSON/Atom Custom Search API Example</title>
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>

  </head>
  <body>
   <div id="content"></div>
   <form method="POST" id="hidden_form" action="file.php" style="display:none;">
		<input type="submit" name="submit" value="Cerca" id="sub_hidden"/> 
  </form>
    <script>
		var count = 0;
		var JSONelenco = new Object;
		JSONelenco.sito = new Array;
		var JSONString;
		var elenco = [];
		function hndlr(response) {
	  
			for (var i = 0; i < response.items.length; i++) {
				var item = response.items[i];
				elenco.push(item);
				document.getElementById("content").innerHTML += item.link+"<br>";
			}

			count++;
			if (count == 4){
				for (var i = 0; i < elenco.length; i++) {
				var item = elenco[i];
				JSONelenco.sito[i] = new Object;
				JSONelenco.sito[i].nome = item.title;
				JSONelenco.sito[i].link = item.link;

			  }
			
			JSONstring = JSON.stringify(JSONelenco);
			var s = "esamina_siti.php?valori="+encodeURIComponent(JSONstring);
			document.getElementById("content").innerHTML += "<br>"+s;
			$("#hidden_form").attr("action","esamina_siti.php?valori="+encodeURIComponent(JSONstring));
			//$("#sub_hidden").trigger("click");
			 /*$.ajax({
					url: "file.php?valori="+encodeURIComponent(JSONstring),
					type: "POST",
					success: function(data){
						alert("success "+data);
					},
					error: function(jqXHR, textStatus, errorThrown) {
						alert("Errore "+textStatus);
					}
				});*/
			}
		}
		
    </script>
	
	<script src="https://www.googleapis.com/customsearch/v1?key=AIzaSyC6QltceFVcWLSTbuHjy58a-BcZXxsBFL8&cx=002086684897779538086:ojni3tynjbk&q=<?php echo $query?> -site:facebook.com -site:twitter.com&callback=hndlr">
    </script>
	<!--<script src="https://www.googleapis.com/customsearch/v1?key=AIzaSyC6QltceFVcWLSTbuHjy58a-BcZXxsBFL8&cx=002086684897779538086:ojni3tynjbk&q=<?php echo $_POST['input_search']?> -site:facebook.com -site:twitter.com&callback=hndlr&start=11">
    </script>
	<script src="https://www.googleapis.com/customsearch/v1?key=AIzaSyC6QltceFVcWLSTbuHjy58a-BcZXxsBFL8&cx=002086684897779538086:ojni3tynjbk&q=<?php echo $_POST['input_search']?> -site:facebook.com -site:twitter.com&callback=hndlr&start=21">
    </script>
	<script src="https://www.googleapis.com/customsearch/v1?key=AIzaSyC6QltceFVcWLSTbuHjy58a-BcZXxsBFL8&cx=002086684897779538086:ojni3tynjbk&q=<?php echo $_POST['input_search']?> -site:facebook.com -site:twitter.com&callback=hndlr&start=31">
    </script>-->
	
	
	
  </body>
</html>
