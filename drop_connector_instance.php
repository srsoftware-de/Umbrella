<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$connector_id = param('id2');

$model = Model::load(['ids'=>$model_id]);
$connector = $model->connector_instances($connector_id);

 
if ($confirm = param('confirm')){
	if ($confirm == 'yes') $connector->delete();
	redirect(getUrl('model',$model_id.'/view'));
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Drop connector "?" ?',$connector->base->id)?></legend>
	<?= t('This will also remove all flows from/to the connector. Are you sure?') ?>
	<a class="button" href="?confirm=yes"><?= t('Yes')?></a>
	<a class="button" href="?confirm=no"><?= t('No')?></a>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
