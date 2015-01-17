<?php
	/*if (!file_exists('data/esecuzione')) {
		$dir_path = 'data/esecuzione';
		mkdir($dir_path, 0777, true);*/
		/*$file_associazioni = fopen($dir_path."/associazioni_".time().".csv", "w");
		$file_email = fopen($dir_path."/elenco-email_".time().".csv", "w");
		$file_numeri = fopen($dir_path."/elenco-numeri_".time().".csv", "w");*/
		/*$file_associazioni = fopen($dir_path."/associazioni.csv", "w");
		$file_email = fopen($dir_path."/elenco-email.csv", "w");
		$file_numeri = fopen($dir_path."/elenco-numeri.csv", "w");
	}
	else{
		echo "impossibile creare la cartella";
	}
	fputcsv($file_associazioni,explode(",","nome associazione,sito,comune,cap,provincia,regione,categoria"));
	fputcsv($file_email,explode(",","sito associazione,email"));
	fputcsv($file_numeri,explode(",","sito associazione,telefono"));
	fclose($file_associazioni);
	fclose($file_email);
	fclose($file_numeri);*/
?>
	<!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
<script>
		var count = 0;
	

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
				alert("done");
				$("#content").append(data);
				//$("#content").append(data);
				count++;
				terminato();
				//alert("success 2");
			},
			error: function(jqXHR, textStatus, errorThrown) {
				count++;
				terminato();
				//alert("2 - Errore "+textStatus);
			}
	});
	
	$.ajax({
			url: "php/script_1.php",
			type: "POST",
			datatype: "json",
			success: function(data){
				//$("#content").append(data);
				//alert("success 1");
				count++;
				terminato();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				//alert("1 - Errore "+errorThrown);
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
	
	function terminato(){
		if (count == 5){
			$.ajax({
			url: "php/crea_dataset.php",
			type: "POST",
			datatype: "json",
			success: function(data){
				$("#content").append(data);
				//alert("success 1");
				
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert("5 - Errore "+errorThrown);
			}
	});
		}
	
	}

    </script>
	
	<body>
		<div id="content"></div>
	</body>