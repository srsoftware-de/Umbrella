<?php $title = 'Umbrella Notes Management';

include '../bootstrap.php';
include 'controller.php';

require_login('notes');

$notes = Note::load();

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

?>
<table>
	<tr>
		<th><?= t('URL') ?></th>
		<th><?= t('note') ?></th>
	</tr>
<?php foreach ($notes as $n) { 
	$note = new Note($n['uri'],$n['note'])
	?>
	<tr>
		<td><a href="<?= $note->url() ?>"><?= $note->uri ?></a></td>
		<td><a href="<?= $note->url() ?>"><?= $note->note ?></a></td>
	</tr>
<?php } ?>
</table>

<?php include '../common_templates/bottom.php';
