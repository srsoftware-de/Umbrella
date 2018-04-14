<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$process_id = param('id2');

if (!$model_id){
	error('No model id passed to terminal.');
	redirect(getUrl('model'));
}
if (!$process_id){
	error('No terminal id passed to terminal.');
	redirect(getUrl('model'));
}

$model = Model::load(['ids'=>$model_id]);
$process = $model->processes($process_id);

$connections = $process->connections();

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<table class="vertical process">
	<tr>
		<th><?= t('Process')?></th>
		<td>
			<span class="right symbol">
				<a href="../edit_process/<?= $process->id ?>" title="<?= t('edit')?>"></a>
				<a href="../connect_process/<?= $process->id ?>" title="<?= t('add connection')?>"></a>
			</span>
			<h1><?= $process->name ?></h1>
		</td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td class="project">
			<a href="<?= getUrl('project',$model->project_id.'/view'); ?>"><?= $model->project['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$model->project_id ?>" class="symbol" title="<?= t('show project files');?>" target="_blank"></a>
			</td>
	</tr>
	<tr>
		<th><?= t('Model')?></th>
		<td class="model">
			<a href="<?= getUrl('model',$model->id.'/view'); ?>"><?= $model->name ?></a>
		</td>
	</tr>
	<?php if ($process->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= $process->description; ?></td>
	</tr>
	<?php } ?>
	<?php if ($process->connections()){ ?>
	<tr>
		<th><?= t('Connections')?></th>
		<td class="connections">
			<ul>
			<?php foreach ($process->connections() as $connection) { ?>
				<li title="<?= $connection->description ?>"><span class="symbol"><?= $connection->direction?'':''?></span> <?= $connection->name ?></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php';