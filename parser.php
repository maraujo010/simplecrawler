<?php

require_once('Document.php');
require_once('Serializer.php');


/* get remote html */
function get_html($url) {
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

	$data = curl_exec($ch);

	curl_close($ch);
	
	if($data === false) {
		die("Error: could not get html \n");
	}
	echo ".... Html content downloaded \n\n";
	return $data;
}


$content = get_html('https://www.landtag.nrw.de/portal/WWW/Webmaster/GB_I/I.1/aktuelle_drucksachen/aktuelle_Dokumente.jsp?wp=14&docTyp=ALLE&datumsart=ge&von=01.10.2015&bis=&searchDru=suchen&maxRows=1000');

$doc = new DOMDocument();

// enable user error handling to hide errors from console
libxml_use_internal_errors(true);
$doc->loadHTML($content);

$table = $doc->getElementsByTagName('table')->item(0);
  
$documents = [];

if ($table!=null) {
	
	$tbody = $table->getElementsByTagName('tbody')->item(0);
	$lines = $tbody->getElementsByTagName('tr');
	
	$counter = 1;
	foreach($lines as $line) {
		
		$anchors = $line->getElementsByTagName('a');
		
		if ($anchors->length==0) {
			continue;
		}
		
		$documents[] = new Document($line, $counter);							
		$counter++;
		
		echo "Document ".$counter.": done!\n\n";

    }   
    
	// serialize Document objects into xml	
	$options = array(
                    XML_SERIALIZER_OPTION_INDENT      => "    ",
                    XML_SERIALIZER_OPTION_LINEBREAKS  => "\n",    
                    XML_SERIALIZER_OPTION_XML_ENCODING => "UTF-8",                 
                    'rootName'             			  => "Documents",
                    'mode'               => 'simplexml',                    
                );

    $serializer = &new XML_Serializer($options);
    $result = $serializer->serialize(["Document" => $documents]);
    
    if( $result === true ) {
		$xml = $serializer->getSerializedData();
		file_put_contents("documents.xml", "<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n".$xml) ;
	}
	else {
		echo "Error serializing objects";
	}
		
	
}



?>

