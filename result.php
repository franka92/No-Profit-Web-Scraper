<?php
	//include "file.php";
	header('Content-Type: text/html; charset=ISO-8859-1');
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
			//document.getElementById("hidden_form")[0].setAttribute("action", "file.php?valori="+encodeURIComponent(JSONstring));
			$("#hidden_form").attr("action","esamina_siti.php?valori="+encodeURIComponent(JSONstring));
			$("#sub_hidden").trigger("click");
			//document.getElementById("hidden_form").submit();
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
	
	<script src="https://www.googleapis.com/customsearch/v1?key=AIzaSyC6QltceFVcWLSTbuHjy58a-BcZXxsBFL8&cx=002086684897779538086:ojni3tynjbk&q=<?php echo $_POST['input_search']?>&callback=hndlr">
    </script>
	<script src="https://www.googleapis.com/customsearch/v1?key=AIzaSyC6QltceFVcWLSTbuHjy58a-BcZXxsBFL8&cx=002086684897779538086:ojni3tynjbk&q=<?php echo $_POST['input_search']?>&callback=hndlr&start=11">
    </script>
	<script src="https://www.googleapis.com/customsearch/v1?key=AIzaSyC6QltceFVcWLSTbuHjy58a-BcZXxsBFL8&cx=002086684897779538086:ojni3tynjbk&q=<?php echo $_POST['input_search']?>&callback=hndlr&start=21">
    </script>
	<script src="https://www.googleapis.com/customsearch/v1?key=AIzaSyC6QltceFVcWLSTbuHjy58a-BcZXxsBFL8&cx=002086684897779538086:ojni3tynjbk&q=<?php echo $_POST['input_search']?>&callback=hndlr&start=31">
    </script>
	
	
	
  </body>
</html>
