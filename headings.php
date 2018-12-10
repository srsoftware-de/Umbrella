<?php
include '../bootstrap.php';

if ($page = param('page')){
	$xml = new DOMDocument();
	@$xml->loadHTMLFile($page);

	$headings = [];
	foreach (['title','h1','h2','h3'] as $tag){
		foreach ($xml->getElementsByTagName($tag) as $head){
			$txt = trim($head->textContent);
			if (strpos($txt,"\n")===false) $headings[]=$txt;
		}
	}

	$keywords = [];
	foreach ($xml->getElementsByTagName('meta') as $tag){
		if ($tag->getAttribute('name') != 'keywords') continue;
		$keywords = explode(',', str_replace(' ','_',str_replace(', ',',',$tag->getAttribute('content'))));
	}
	die(json_encode(['headings'=>$headings,'keywords'=>$keywords],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
} else {
	echo 'No path set!';
}
