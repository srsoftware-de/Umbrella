<?php include 'controller.php';

require_login('notes');

$options = ['ids' => param('id')];

$note = Note::load($options);

if ($new_code = param('code')){
	$note->patch(['note'=>$new_code]);
	$note->save();
	redirect($note->url());
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

?>
<table class="note">
	<tr>
		<th><?= t('usage') ?></th>
		<th><?= t('code') ?></th>
		<th><?= t('rendered output') ?></th>
	</tr>
	<tr>
		<td><a href="<?= $note->url() ?>"><?= $note->uri ?></a></td>
		<td id="preview"><?= markdown($note->note) ?></td>
		<td class="code">
			<form method="POST">
				<textarea id="preview-source" name="code"><?= htmlspecialchars($note->note) ?></textarea>				
				<button type="submit"><?= t('Save') ?></button>
			</form>
		</td>
	</tr>
</table>
<?php include '../common_templates/closure.php';