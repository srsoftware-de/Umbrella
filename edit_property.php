<?php include 'controller.php';

require_login('stock');

if ($id = param('id')){
	$property = Property::load(['ids'=>$id]);

	if ($name = param('name')){
		$property->patch($_POST)->save();
		redirect(param('redirect',$base_url));
	}
} else error('No porperty id passed to edit_property!');


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend><?= t('Edit property') ?></legend>
	<table>
		<tr>
			<th><?= t('Field')?></th>
			<th><?= t('Value')?></th>
		</tr>
		<?php foreach ($property as $key => $value){ if (in_array($key, ['dirty','id'])) continue;?>
		<tr>
			<td><?= t($key) ?></td>
			<td><input type="text" name="<?= $key ?>" value="<?= $value ?>" /></td>
		</tr>
		<?php }?>
	</table>
	<button type="submit"><?= t('Save') ?></button>
</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>