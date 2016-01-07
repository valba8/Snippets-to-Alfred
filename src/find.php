

<?php

$words = $argv[1];
$searchWords = explode(" ", $words);

$bd = new SQLite3(getenv('HOME').'/Library/Application Support/Snippets/Snippets.sqlite');
$sql = array(); 

foreach($searchWords as $word){
	$lowerWord = strtolower($word);
    $sql[] = 'lower(Name) LIKE "%'.$lowerWord.'%"';
}

$sql = 'SELECT * FROM Label WHERE '.implode(' OR ', $sql);
$results = $bd->query($sql);

$inQuery = '('; 
$index = 0;
while ($row = $results->fetchArray()) {
	if ($index>0){
		$inQuery = $inQuery.',';
	}
	$inQuery = $inQuery.'"'.$row['Key'].'"';
	$index+=1;
}
$inQuery = $inQuery.')';


$sqlSnippetsKeys = 'SELECT distinct(SnippetKey) FROM SnippetLabels WHERE LabelKey in '.$inQuery;
$resultsSnippetsKeys = $bd->query($sqlSnippetsKeys);

$inQuerySnippets = '('; 
$index=0;
while ($rowSnippetsKeys = $resultsSnippetsKeys->fetchArray()) {
	if ($index>0){
		$inQuerySnippets = $inQuerySnippets.',';
	}
	$inQuerySnippets = $inQuerySnippets.'"'.$rowSnippetsKeys['SnippetKey'].'"';
	$index+=1;
}
$inQuerySnippets = $inQuerySnippets.')';


$sqlSnippets = 'SELECT * FROM Snippet WHERE Key in '.$inQuerySnippets;
$resultsSnippets = $bd->query($sqlSnippets);

echo '<?xml version="1.0"?>';
echo '<items>';

while ($snippet = $resultsSnippets->fetchArray()) {
		$sourceCode = preg_replace_callback('/(\\\\+)u([0-9a-fA-F]{4})/u', function($match) {
			return $match[1] == '\\\\' ? $match[0] : mb_convert_encoding(pack('H*', $match[2]), 'UTF-8', 'UCS-2LE');
		}, $snippet['SourceCode']);

		$sourceCode = str_replace('\\\\', '\\', $sourceCode);

		echo "<item uid=\"".$snippet['Key']."\" arg=\"".base64_encode($sourceCode)."\">\n";
		echo "<title>" . $snippet['Name'] . "</title>\n";
		echo "<subtitle>" . htmlspecialchars($sourceCode, ENT_QUOTES, 'utf-8') . "</subtitle>\n";
		echo " <subtitle mod=\"shift\">".$snippet['Comment']."</subtitle>\n";
		echo "<icon>icon.png</icon>\n";
		echo "<valid>yes</valid>\n";
		echo " </item>\n";
}

echo '</items>';
