<?php include 'controller.php';

require_login('notes');

if ($key = param('key')){
	$notes = Note::load(['key'=>$key]);
	if (!empty($notes)){ ?>
		<table class="notes">
		<tr>
		<th><a href="<?= getUrl('notes','?order=uri&limit=0')?>"><?= t('Use&nbsp;/ URI') ?></a></th>
				<th><?= t('note') ?></th>
			</tr>
		<?php foreach ($notes as $note) { ?>
			<tr>
				<td><a href="<?= $note->url() ?>"><?= $note->uri ?></a></td>
				<td class="note"><?= $parsedown?$parsedown->parse($note->note):str_replace("\n", "<br/>", $note->note) ?></td>
			</tr>
		<?php } ?>
		</table>
	<?php }
}