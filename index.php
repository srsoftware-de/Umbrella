<?php $title = 'Umbrella Notes Management';

include '../bootstrap.php';
include 'controller.php';

require_login('notes');

$options = [];
if ($order = param('order')) $options['order'] = $order;
if (($limit = param('limit')) !== null) $options['limit'] = $limit;

$notes = Note::load($options);

if (file_exists('../lib/parsedown/Parsedown.php')){
	include '../lib/parsedown/Parsedown.php';
	$parsedown  = Parsedown::instance();
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

?>
<table class="notes">
	<tr>
		<th><a href="<?= getUrl('notes','?order=uri&limit=0')?>"><?= t('URI') ?></a></th>
		<th><?= t('note') ?></th>
	</tr>
<?php foreach ($notes as $n) { 
	$note = new Note($n['uri'],$n['note'])
	?>
	<tr>
		<td><a href="<?= $note->url() ?>"><?= $note->uri ?></a></td>
		<td class="note"><?= $parsedown?$parsedown->parse($note->note):str_replace("\n", "<br/>", $note->note) ?></td>
	</tr>
<?php } ?>
</table>

<?php include '../common_templates/closure.php';