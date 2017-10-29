<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';

require_login('tag');

$url = param('url');
if ($url){
	$tag = end(explode('/',$url)); // replace u0020 by u00a0
	$dom = new DOMDocument();
	$dom->loadHTMLFile($url);
	$divs=$dom->getElementsByTagName('div');
	foreach ($divs as $div){
		if (!$div->hasAttribute('class')) continue;
		if ($div->getAttribute('class') != 'articleThumbBlock ') continue;
		$headings = $div->getElementsByTagName('h3');
		$title = null;
		foreach ($headings as $heading){
			$anchors = $heading->getElementsByTagName('a');
			if ($title === null) $title = $heading->nodeValue;
			foreach ($anchors as $anchor){
				if ($anchor->hasAttribute('title')) $title = $anchor->getAttribute('title');
			}
		}
		
		$anchors = $div->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('target')) continue;
			if (!$anchor->hasAttribute('href')) continue;
			$href = $anchor->getAttribute('href');
			print '<li>'.$href."</li>\n";
			save_tag($href,$tag,$title,false);
			break;
		}
		
	}
	die();
}

$user_name = param('delicious_user_name');
$links = null;
if ($user_name){
	$dom = new DOMDocument();
	@$dom->loadHTMLFile('https://del.icio.us/'.$user_name.'/tags?sort=alpha');
	$divs = $dom->getElementsByTagName('div');
	$links = [];
	foreach ($divs as $div){
		if (!$div->hasAttribute('class')) continue;
		$classes = explode(' ',$div->getAttribute('class'));
		if (in_array('tags', $classes)){
			$anchors = $div->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;				
				$href = $anchor->getAttribute('href');
				$key = end(split('/', $href));
				$links[$key]=$href;
			}	
		}		 
	}
}

				 



include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<?php if ($links){ set_time_limit(0); ?>
<fieldset>
	<legend>Delicious tags</legend>
	<ul>
	<?php foreach ($links as $key => $link) { ?>
		<li>
			<?= $key ?>
			<ul href="<?= str_replace('"', '%22', $link) ?>"></ul>
		</li>		
	<?php } ?>
	</ul>
</fieldset>
<script type="text/javascript">
	function import_listing(listings){
		var ul = listings.pop();
		var xhr = new XMLHttpRequest();
		var url = ul.getAttribute('href');
		xhr.open('GET', '<?= getUrl('tag','import?url='); ?>'+url);
		xhr.onload = function() {
		    if (xhr.status === 200) {
		        ul.innerHTML=xhr.responseText;
		       	setTimeout(import_listing, 1, listings);
		       	var li=ul.parentElement;
		       	setTimeout(function(){ li.parentElement.removeChild(li); },30000); 
		    }
		    else {
		    	ul.innerHTML='<li>failed</li>';
		        console.log('Request failed.  Returned status of ' + xhr.status);
		        setTimeout(import_listing, 1, listings); 
		    }
		};
		xhr.send();				
	}
		
	var elements = document.getElementsByTagName('ul');
	var listings = [];
	for (var i=elements.length-1; i>=0; i--){
		if (elements[i].hasAttribute('href')) listings.push(elements[i]); 
	}
	import_listing(listings);
</script>

<?php } ?>

<form method="POST">
<fieldset>
	<legend>Import tags from delicious</legend>
	Delicious user name: <input type="text" name="delicious_user_name" /><br/>
	<em>This may take a very long time!</em>
	<input type="submit" />
</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?> 