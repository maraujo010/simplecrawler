<?php

class Document
{

	public $ui = "";
	public $doc_id = "";
	public $pub_date_source = "";
	public $pub_date_record = "";
	public $source = "Parlament";
	public $location = "NRW";
	public $doctype = "";
	public $author = "";
	public $title = "";
	public $full_text = "";
	public $source_doc_link = "";
	public $official_record_nr ="";
	
	
	public function Document(DOMElement $line, $ui) {		
		
		$this->ui = $ui;
		
		$columns = $line->getElementsByTagName('td');				
		
		$this->pub_date_record = date('d-m-Y');
		$this->pub_date_source = $this->getDocDate($columns->item(0));
		$this->source_doc_link = $this->getDocLink($columns->item(0));
		$this->doctype = $columns->item(2)->nodeValue;
		$this->full_text = $this->getFullTextFromPDF($this->source_doc_link);
		$this->doc_id = preg_replace("/\//", "", $this->official_record_nr);
		$this->title = $this->getDocTitle($columns->item(3));
		$this->author = $this->getDocAuthor($columns->item(3)->nodeValue);
	}
	
	/* get document author */
	private function getDocAuthor($nodeValue) {
		$author = "";
		$pos = strpos($nodeValue, "Urheber:");
		
		if ($pos!=false) {
			$author = trim(substr($nodeValue, $pos+8));			
		}
		
		return $author;
	}
	
	/* get document title */
	private function getDocTitle(DOMElement $column) {
		$anchors = $column->getElementsByTagName('a');	
		$anchor = $anchors->item(0);										
				
		return $anchor->nodeValue; 
	}
	
	/* get document date */
	private function getDocDate(DOMElement $column) {
		$dateTag = $column->getElementsByTagName('nobr');	
		$docDate = $dateTag->item(0)->nodeValue;
		$docDate = preg_replace("/\./", "-", $docDate);	
		
		if (preg_match("/(\d{2})-(\d{2})-(\d{4})/", $docDate, $match)) {
			$docDate = $match[0];
		}
		
		
		return $docDate;
	}
	
	/* get pdf document link */
	private function getDocLink(DOMElement $column) {
		
		$anchors = $column->getElementsByTagName('a');	
		$anchor = $anchors->item(0);		
						
		$url = "https://www.landtag.nrw.de".$anchor->getAttribute('href');			
				
		$this->official_record_nr = $anchor->nodeValue; 
		
		return $url;
	}
	
	/* extract full text from pdf document */
	private function getFullTextFromPDF($url) {		
		
		$error = false;
		$text = "";

		$fp = fopen ('file.pdf', 'w');
		$ch = curl_init();				
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FILE, $fp); 	
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);			
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);	
		curl_setopt($ch, CURLOPT_HEADER, 0);			
		curl_exec($ch); 
		
		if(curl_errno($ch))
		{
			$error = true;
		}
		
		curl_close($ch);
		fclose($fp);

		if(!file_exists('file.pdf') || $error){
			echo "Error downloading pdf! \n";			
		}		
		else {
			
			echo "PDF file downloaded \n";
		
			$cmd = "pdftotext -layout file.pdf file.txt";
			exec($cmd);
			
			unlink('file.pdf');
			
			if(!file_exists('file.txt') || $error){
				echo "Error converting pdf to text! \n";			
			}
			else {				
				$text = file_get_contents("file.txt");				
				
				// some filtering			
				$text = trim(preg_replace("/\s{4,}/", "\n", $text));			
				$text = preg_replace("/[^\p{L}\p{N}\-\(\)\%\$\n\@\/\. ]/u", "", $text);												
				$text = preg_replace("/\n{3,}/", "\n\n", $text);								
				
				unlink('file.txt');
			}						
		}				
		
		return $text;
		
	}

}


?>
