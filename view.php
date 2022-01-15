<?php include 'controller.php';

require_login('notes');

$options = ['ids' => param('id')];

$note = Note::load($options);

if ($new_code = param('code')){
	$note->patch(['note'=>$new_code]);
	$note->save();
	redirect(url($note->uri));
}

$editor = true;
$view = true;
$mode = param('mode','editor+preview');
switch ($mode){
    case 'view': $editor = false; break;
    case 'editor' : $view = false; break;
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

?>
<table class="note">
	<tr>
		<th><?= t('usage') ?></th>
		<?php if ($editor) { ?><th><?= t('code') ?></th><?php } ?>
		<?php if ($view) {    ?><th><?= t('rendered output') ?></th><?php } ?>
	</tr>
	<tr>
		<td><a href="<?= $url($note->uri) ?>"><?= $note->uri ?></a></td>
		<?php if ($editor) { ?>
		<td class="code">
			<form method="POST">
				<textarea id="preview-source" name="code"><?= htmlspecialchars($note->note) ?></textarea>				
				<button type="submit"><?= t('Save') ?></button>
			</form>
		</td>
		<?php } ?>
		<?php if ($view) { ?>
		<td id="preview"><?= markdown($note->note) ?></td>
		<?php } ?>
	</tr>
</table>
<?php include '../common_templates/closure.php';