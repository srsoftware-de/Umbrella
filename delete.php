<?php $title = 'Umbrella Notes Management';

include '../bootstrap.php';
include 'controller.php';

require_login('notes');

if ($id = param('id')){
	$notes = Note::load(['ids'=>$id]);
	if (empty($notes)){
		error('There is no note with id "?" or you are not allowed to access it!');
	} else {
		foreach ($notes as $id => $note) $note['id'] = $id;
		if (param('confirm') == 'yes'){
			$note = new Note($note['uri'],$note['note']);
			if (Note::delete($id)) redirect($note->url());
		}
	}
} else {
	error('No note id passed along with delete call!');
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

?>

<?php if ($note) { ?>
<h2><?= t('This will remove the following note:')?></h2>
<fieldset class="del_note">
	<legend><?= $user->login ?></legend>
	<?= $note['note']?>
</fieldset>
<?= t('Are you sure?')?><br/>
<a href="?confirm=yes<?= $target?('&redirect='.$target):''?>" class="button"><?= t('Yes')?></a>
<a href="view" class="button"><?= t('No')?></a>

<?php } ?>
<?php include '../common_templates/bottom.php';
