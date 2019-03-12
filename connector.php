<?php include 'controller.php';

require_login('model');

$process_connector_id = param('id');
if (empty($process_connector_id)) {
	error('No process connector id passed to form!');
	redirect(getUrl('model'));
}

$process_place_id = param('place_id');
if (empty($process_place_id)){
	$connector = Connector::load(['ids'=>$process_connector_id]);
} else {

}



if ($name = param('name')){
	$connector->patch(['name'=>$name])->save();
	redirect(getUrl('model'));
}

$occurences = $connector->occurences();
$redirect = getUrl('model',empty($occurences)?'':'process/'.reset($occurences)->id);
if (param('action') == 'delete'){
	$connector->delete();
	redirect($redirect);
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>


<fieldset>
	<legend><?= t('Connector')?></legend>
	<form method="POST">
		<?= t('Name:')?> <input type="text" name="name" value="<?= param('name',$connector->name)?>"/>
	</form>
	<button type="submit"><?= t('Save')?></button>
	<a class="button" href="?action=delete"><?= t('delete') ?></a>
</fieldset>

<?php if (param('action') == 'delete'){ ?>
<fieldset>
	<legend>
		<?= t('Really delete this connector?')?>
		<?= t('This will also remove all flow from and to instances of this connector!')?>
	</legend>
	<a class="button" href="?action=delete&confirm=yes"><?= t('Yes')?></a>
	<a class="button" href="<?= $redirect ?>"><?= t('No')?></a>
</fieldset>
<?php }?>

<?php include '../common_templates/closure.php'; ?>
