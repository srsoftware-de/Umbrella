<?php include 'controller.php';

require_login('model');

$projects = request('project','json');
if (empty($projects)) warn('Models must be assigned to projects. You have not created any projects, yet.');

$options = [];
if ($project_id = param('project')) $options['project_id'] = $project_id;
$diagrams = Diagram::load($options);

$options['models']=true;
$models = Process::load($options);

foreach ($models as $model) $projects[$model->project_id]['models'][$model->id] = $model;
foreach ($diagrams as $diagram) $projects[$diagram->project_id]['diagrams'][$diagram->id] = $diagram;

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

?>
<h2><?= t('Models') ?></h2>

<?php
foreach ($projects as $project){
	if (isset($project['models']) || isset($project['diagrams'])){ ?>

<fieldset>
	<legend>
		<a href="<?= getUrl('project',$project['id'].'/view') ?>"><?= $project['name'] ?></a>
		<span class="symbol">
			<a title="<?= t('add model') ?>" href="add_model_to_project/<?= $project['id'] ?>"></a>
			<a title="<?= t('add network diagram') ?>" href="add_diagram_to_project/<?= $project['id'] ?>"></a>
			<a title="<?= t('export all models of this project') ?>" href="export?project=<?= $project['id'] ?>"></a>
		</span>
	</legend>

<?php foreach ($project['models'] as $model) { ?>
	<a class="button" href="<?= getUrl('model','model/'.$model->id) ?>"><?= $model->name ?></a>
<?php }?>
<?php foreach ($project['diagrams'] as $diagram) { ?>
	<a class="button" href="<?= getUrl('model','diagram/'.$diagram->id) ?>"><?= $diagram->name ?></a>
<?php }?>
<?= (isset($services['notes']) && $project_id) ? request('notes','html',['uri'=>'model:project:'.$project_id],false,NO_CONVERSION) : '' ?>
</fieldset>
<?php }
} ?>
<fieldset>
	<legend><?= t('Add model to project:')?></legend>
<?php
foreach ($projects as $project){
	if (!isset($project['models'])){ ?>
	<a class="button" href="add_model_to_project/<?= $project['id'] ?>"><?= $project['name']?></a>
<?php }
} ?>
</fieldset>
<?php include '../common_templates/closure.php';