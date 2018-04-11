<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

if ($id = param('id')){
	$models = Model::load(['ids'=>$id]);
	$model = reset($models);
} else {
	error('No model id passed!');
	redirect(getUrl('model'));
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

?>
<h2><?= t('Model "?"',$model->name) ?></h2>
<?= $model->description ?>
<?php include '../common_templates/closure.php';