<?php
include '../bootstrap.php';

if ($page = param('page')){
	$xml = new DOMDocument();
	@$xml->loadHTMLFile($page);
	
	$headings = [];
	foreach (['h1','h2','h3'] as $tag){
		foreach ($xml->getElementsByTagName($tag) as $head){
			$txt = trim($head->textContent);
			if (strpos($txt,"\n")===false) $headings[]=$txt;
		}
		if (!empty($headings)) break;
	}
	die(json_encode($headings));
} else {
	echo 'No path set!';
}