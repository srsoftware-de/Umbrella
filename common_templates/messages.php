<?php
foreach (['errors','warnings','infos'] as $cat){
if (isset($_SESSION[$cat]) && !empty($_SESSION[$cat])) { ?>
<div class="<?= $cat ?>">
	<?php while (!empty($_SESSION[$cat])) { ?>
	<span><?= array_shift($_SESSION[$cat]) ?></span>
	<?php } ?>
</div>
<?php } // errors
}