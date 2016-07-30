<?php
include '../bootstrap.php';

$entries = request('project', 'ctrl/menu'); ?>
<h3 class="project menu">Project menu</h3>
<ul>
	<?php foreach ($entries as $action => $text) { ?>
	<li><a href="<?php echo getUrl('project', 'form/'.$action); ?>"><?php print $text; ?></a></li>
	<?php } ?>
</ul>