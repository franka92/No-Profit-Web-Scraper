<?php

?>
	<!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
<script>

	

		$.ajax({
			url: "php/script_3.php",
			type: "POST",
			datatype: "json",
			success: function(data){
			$("#content").append(data);
				//alert("success 3");
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert("3 - Errore "+textStatus);
			}
	});
	
			$.ajax({
			url: "php/script_2.php",
			type: "POST",
			datatype: "json",
			success: function(data){
				$("#content").append(data);
				//alert("success 2");
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert("2 - Errore "+textStatus);
			}
	});
	
	$.ajax({
			url: "php/script_1.php",
			type: "POST",
			datatype: "json",
			success: function(data){
				$("#content").append(data);
				//alert("success 1");
				
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert("1 - Errore "+errorThrown);
			}
	});
	
	$.ajax({
			url: "php/script_5.php",
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
	
	$.ajax({
			url: "php/script_4.php",
			type: "POST",
			datatype: "json",
			success: function(data){
				$("#content").append(data);
				//alert("success 1");
				
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert("4 - Errore "+errorThrown);
			}
	});

    </script>
	
	<body>
		<div id="content"></div>
	</body>