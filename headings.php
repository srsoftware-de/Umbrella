<?php
include '../bootstrap.php';

if ($page = param('page')){
	$xml = new DOMDocument();
	@$xml->loadHTMLFile($page);
	
	$headings = [];
	foreach ($xml->getElementsByTagName('h1') as $head){
		$txt = $head->nodeValue;
		if (strpos($txt,"\n")===false) $headings[]=$txt;
	}
	foreach ($xml->getElementsByTagName('h2') as $head){
		$txt = $head->nodeValue;
		if (strpos($txt,"\n")===false) $headings[]=$txt;
	}
	die(json_encode($headings));
} else {
	echo 'No path set!';
}