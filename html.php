<?php include 'controller.php';

require_login('notes');

$uri = param('uri');
$form = param('form',true);
assert($uri !== null,'Called notes/json without uri');
$notes = Note::load(['uri'=>$uri,'limit'=>0,'order'=>'id']);
$users = request('user','json');

if (file_exists('../lib/parsedown/Parsedown.php')){
	include '../lib/parsedown/Parsedown.php';
	$parsedown  = Parsedown::instance();
}

foreach ($notes as $nid => $note){ ?>
	<fieldset class="note" id="bkmk<?= $note->id ?>">
		<legend><?= $users[$note->user_id]['login'] . ((isset($note->timestamp) && $note->timestamp>0) ? ' - '.date(t('Y-m-d H:i:s'),$note->timestamp) : '') ?></legend>
		<?php if ($note->user_id == $user->id) {?>
		<span class="right">
			<a class="symbol" href="<?= getUrl('notes',$nid.'/view') ?>" title="<?= t('edit note')?>"></a>
			<a class="symbol" href="<?= getUrl('notes',$nid.'/delete') ?>" title="<?= t('delete note')?>"></a>
		</span>
		<?php }?>
		<?= $parsedown?$parsedown->parse($note->note):str_replace("\n", "<br/>", $note->note) ?>
	</fieldset>
<?php } ?>

<?php if ($form) { ?>
	<form action="<?= getUrl('notes','add') ?>" method="POST">
		<input type="hidden" name="uri" value="<?= $uri ?>" />
		<input type="hidden" name="token" value="<?= $_SESSION['token'] ?>" />
		<fieldset class="add note">
			<legend><?= t('add note - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>','https://www.markdownguide.org/cheat-sheet')?></legend>
			<textarea name="note"></textarea>
			<button type="submit"><?= t('add note') ?></button>
		</fieldset>
	</form>
<?php } ?>