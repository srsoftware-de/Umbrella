<?php $title = 'Umbrella Notes Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$projects = request('project','json');
$project_ids = array_keys($projects);

if ($project_id = param('project')){
	if (array_key_exists($project_id,$project_ids)) $projects = [$project_id => $projects[$project_id]];
}


$models = Model::load(['projects'=>$project_ids]);

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

?>
<h1><?= t('Models') ?></h1>
<?php foreach ($projects as $pid => $project){ ?>
<fieldset>
	<legend>
		<?= t('Project: ?',$project['name']) ?>
		<a class="symbol" title="<?= t('add model') ?>" href="add?project=<?= $pid ?>"></a>
	</legend>
	<?php foreach ($models as $id => $model){
		if ($model['project_id'] != $pid) continue; ?>
	<a class="button" href="<?= $id ?>/view"><?= $model['name'] ?></a>
	<?php }?>
</fieldset>
<?php }

include '../common_templates/closure.php';