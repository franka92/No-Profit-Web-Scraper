<?php

?>
	<!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
<script>
	var count = 0;
	$(document).ready(esegui);
	function esegui(){
		$.ajax({
			url: "php/script_3.php",
			type: "POST",
			datatype: "json",
			success: function(data){
				count++;
				terminato();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				count++;
				terminato();
			}
		});
	
			$.ajax({
			url: "php/script_2.php",
			type: "POST",
			datatype: "json",
			success: function(data){
				count++;
				terminato();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				count++;
				terminato();
			}
		});
	
		$.ajax({
				url: "php/script_1.php",
				type: "POST",
				datatype: "json",
				success: function(data){
					count++;
					terminato();
				},
				error: function(jqXHR, textStatus, errorThrown) {
					count++;
					terminato();
				}
		});
	
		$.ajax({
				url: "php/script_5.php",
				type: "POST",
				datatype: "json",
				success: function(data){
					count++;
					terminato();
					
				},
				error: function(jqXHR, textStatus, errorThrown) {
					count++;
					terminato();
				}
		});
		
		$.ajax({
				url: "php/script_4.php",
				type: "POST",
				datatype: "json",
				success: function(data){
					count++;
					terminato();
					
				},
				error: function(jqXHR, textStatus, errorThrown) {
					count++;
					terminato();
				}
		});
	}
	
	function terminato(){
		if (count >= 5){
			$.ajax({
			url: "php/crea_dataset.php",
			type: "POST",
			datatype: "text",
			success: function(data){
				if(data == "ok")
					alert ("ok");
				else if (data == "no")
					esegui();
				else
					alert("Si è verificato un errore imprevisto! Ricarica la pagina!");
				
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert("5 - Errore "+errorThrown);
			}
		});
			//alert("tutti finito");
		}
	
	}

    </script>
	
	<body>
		<div id="content"></div>
	</body>