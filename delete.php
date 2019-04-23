<?php include 'controller.php';

require_login('notes');

if ($id = param('id')){
	$note = Note::load(['ids'=>$id]);
	if ($note === null){
		error('There is no note with id "?" or you are not allowed to access it!');
	} else if (param('confirm') == 'yes' && Note::delete($id)) redirect($note->url());
} else {
	error('No note id passed along with delete call!');
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

?>

<?php if ($note) { ?>
<h2><?= t('This will remove the following note:')?></h2>
<fieldset class="del_note">
	<legend><?= $user->login ?></legend>
	<?= $parsedown?$parsedown->parse($note->note):str_replace("\n", "<br/>", $note->note) ?>
</fieldset>
<?= t('Are you sure?')?><br/>
<a href="?confirm=yes<?= empty($target)?'':('&redirect='.$target)?>" class="button"><?= t('Yes')?></a>
<a href="view" class="button"><?= t('No')?></a>

<?php }

include '../common_templates/closure.php';
