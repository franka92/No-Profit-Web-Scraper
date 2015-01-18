<html>
  <head>
    <title>Ricerca Siti</title>
	<!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
	
	<script src="lib/jquery.csv-0.71.js"></script>
	<script src="js/aggiorna_filtri.js" contentType='application/json; charset=utf-8'></script>
  </head>
  <body>
  
	  <form method="POST" id="search" action="php/result.php">
			<input type="text" id="input_search" name="input_search"></input>
			<input type="submit" name="submit" value="Cerca" id="bt_cerca"> 
			<div id="elenco_regioni">
				
			</div>
			<div id="elenco_province">
				
			</div>
	  </form>
	  
	
  </body>
</html>
