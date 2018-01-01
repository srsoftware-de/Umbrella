<?php
include '../bootstrap.php';
include 'controller.php';

require_login('notes');

$uri = param('uri');
assert($uri !== null,'Called notes/json without uri');
$notes = Note::load(['uri'=>$uri]);

$users = request('user','list');

foreach ($notes as $nid => $note){ ?>
	<fieldset>
		<legend><?= $users[$note['user_id']]['login'] ?></legend>
		<?php if ($note['user_id'] == $user->id) {?>
		<span class="right">
			<a class="symbol" href="<?= getUrl('notes',$nid.'/delete') ?>"></a>
		</span>
		<?php }?>
		<?= $note['note'] ?>
	</fieldset>
	<?php } ?>
	<form action="<?= getUrl('notes','add') ?>" method="POST">
		<input type="hidden" name="uri" value="<?= $uri ?>" />
		<input type="hidden" name="token" value="<?= $_SESSION['token'] ?>" />
		<fieldset>
			<legend><?= t('add note') ?></legend>
			<textarea name="note"></textarea>
			<button type="submit"><?= t('add note') ?></button>		
		</fieldset>
	</form>
