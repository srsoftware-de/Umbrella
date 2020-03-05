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
				<td><a href="<?= $note->url() ?>"><?= emphasize($note->uri,$key) ?></a></td>
				<td class="note"><?= markdown(emphasize($note->note,$key)) ?></td>
			</tr>
		<?php } ?>
		</table>
	<?php }
}