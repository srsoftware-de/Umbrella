<?php
include '../bootstrap.php';

$entries = request('user', 'ctrl/menu'); ?>
<h3 class="user menu">User menu</h3>
<ul>
	<?php foreach ($entries as $action => $text) { ?>
	<li><a href="<?php echo getUrl('user', 'form/'.$action); ?>"><?php print $text; ?></a></li>
	<?php } ?>
</ul>