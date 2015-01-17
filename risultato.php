<?php
	include ("cerca_informazioni.php");
	$handle=fopen("processore1.result","r");

	$risultato1=fread($handle,8000);

	fclose($handle);

//metto in risultato1 la somma parziale di thread1.php

$handle=fopen("processore2.result","r");

$risultato2=fread($handle,8000);

fclose($handle);

//metto in risultato2 la somma parziale di thread2.php

echo "Stampo: ";
stampaElenco($risultato1);
stampaElenco($risultato2);

//echo $risultato1+$risultato2;

echo "
";

//visualizzo la somma

$handle=fopen("processore1.time","r");

$time1=fread($handle,8000);

fclose($handle);

//prendo il tempo di thread1.php

$handle=fopen("processore2.time","r");

$time2=fread($handle,8000);

fclose($handle);

//prendo il tempo di thread2.php

if ($time1>$time2)

echo "Tempo impiegato: $time1 secondi";

else

echo "Tempo impiegato: $time2 secondi";

//visualizzo il tempo maggiore tra thread1.php e thread2.php come tempo totale

?>