<?php $title = 'Umbrella Bookmark Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');
$tags = Tag::load(['order'=>'tag ASC']);
debug($tags,1);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<div class="hover right-fix">
	<?php
		$break =false; 
		foreach (['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'] as $letter) { ?>
	<a class="button" href="#<?= $letter ?>">&nbsp;&nbsp;<?= $letter; ?>&nbsp;&nbsp;</a>
	<?= $break ? '<br/>':''?>
	<?php $break = !$break; }?>
</div>
<fieldset>
	<legend>Tags</legend>
<?php
	$letter = null;
	foreach ($tags as $tag => $dummy){
		$id = false;
		$char = strtoupper($tag[0]);
		if ($letter != $char){
			$letter = $char;
			$id = true;
		} // if ?>
	<a <?= $id ?'id="'.$letter.'" ':' ' ?>class="button" href="<?= urlencode($tag).'/view' ?>"><?= $tag ?></a>
<?php } ?>
</fieldset>

<?php include '../common_templates/closure.php'; ?> 
