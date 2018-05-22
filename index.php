<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$projects = request('project','json');
$options = [];
if ($project = param('project')) $options['project_id'] = $project;
$models = Model::load($options);

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

?>
<h2><?= t('Models') ?></h2>

<?php
foreach ($models as $model) $projects[$model->project_id]['models'][$model->id] = $model;
foreach ($projects as $project){
	if (isset($project['models'])){ ?>
<fieldset>
	<legend>
		<a href="<?= getUrl('project',$project['id'].'/view') ?>"><?= $project['name'] ?></a>
		<span class="symbol">
			<a title="<?= t('add model') ?>" href="add?project=<?= $project['id'] ?>"></a>
			<a title="<?= t('export all models of this project') ?>" href="export?project=<?= $project['id'] ?>"></a>
		</span>
	</legend>
<?php foreach ($project['models'] as $model) { ?>
	<a class="button" href="<?= $model->id ?>/view"><?= $model->name ?></a>
<?php }?>
</fieldset>
<?php }
} ?>
<fieldset>
	<legend><?= t('Add model to project:')?></legend>
<?php 
foreach ($projects as $project){
	if (!isset($project['models'])){ ?>
	<a class="button" href="add?project=<?= $project['id'] ?>"><?= $project['name']?></a>
<?php }
} ?>
</fieldset>
<?php include '../common_templates/closure.php';