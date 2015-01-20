<?php

include('stem.class.php');
//phpinfo();
$stemmer = new ItalianStemmer();

$parola = "armoniosamente";
$stemmed_word = $stemmer->stem($parola);
echo sprintf("%-30s%s\n", $parola, $stemmed_word);

$parola = "armonia";
$stemmed_word = $stemmer->stem($parola);
echo sprintf("%-30s%s\n", $parola, $stemmed_word);

$parola = "musicalmente";
$stemmed_word = $stemmer->stem($parola);
echo sprintf("%-30s%s\n", $parola, $stemmed_word);

$parola = "musicale";
$stemmed_word = $stemmer->stem($parola);
echo sprintf("%-30s%s\n", $parola, $stemmed_word);

echo stem_english('judges'); //Returns the stem, "judg"

?>