<?php
include '../bootstrap.php';
include 'controller.php';

require_login('notes');

$uri = param('uri');
assert($uri !== null,'Called notes/json without uri');
$notes = Note::load(['uri'=>$uri]);
$users = request('user','json');

if (file_exists('../lib/parsedown/Parsedown.php')){
	include '../lib/parsedown/Parsedown.php';
	$parsedown  = Parsedown::instance();
}

foreach ($notes as $nid => $note){ ?>
	<fieldset>
		<legend><?= $users[$note['user_id']]['login'] . ((isset($note['timestamp']) && $note['timestamp']>0) ? ' - '.date(t('Y-m-d H:i:s'),$note['timestamp']) : '') ?></legend>
		<?php if ($note['user_id'] == $user->id) {?>
		<span class="right">
			<a class="symbol" href="<?= getUrl('notes',$nid.'/delete') ?>"></a>
		</span>
		<?php }?>
		<?= $parsedown?$parsedown->parse($note['note']):str_replace("\n", "<br/>", $note['note']) ?>
	</fieldset>
	<?php } ?>
	<form action="<?= getUrl('notes','add') ?>" method="POST">
		<input type="hidden" name="uri" value="<?= $uri ?>" />
		<input type="hidden" name="token" value="<?= $_SESSION['token'] ?>" />
		<fieldset class="add invoice">
			<legend><?= t('add note - <a target="_blank" href="?">click here for Markdown and extended Markdown cheat sheet</a>','https://www.markdownguide.org/cheat-sheet')?></legend>
			<textarea name="note"></textarea>
			<button type="submit"><?= t('add note') ?></button>		
		</fieldset>
	</form>