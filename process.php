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
$process = Process::load(['model_id'=>$model_id,'ids'=>$process_id]);
$action = param('action');
if ($action == 'delete' && param('confirm')=='true'){
	$process->delete();
	redirect($model->url());
}

$connectors = $process->connectors();

if (file_exists('../lib/parsedown/Parsedown.php')){
	include '../lib/parsedown/Parsedown.php';
	$process->base->description = Parsedown::instance()->parse(htmlentities($flow->base->description));
} else {
	$process->base->description = str_replace("\n", "<br/>", htmlentities($process->base->description));
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; 

if ($action == 'delete'){?>
	<fieldset>
		<legend><?= t('Delete "?"',$process->base->id)?></legend>
		<?= t('You are about to delete the process "?". Are you sure you want to proceed?',$process->base->id) ?>
		<a class="button" href="?action=delete&confirm=true"><?= t('Yes')?></a>
		<a class="button" href="?"><?= t('No')?></a>
	</fieldset>
<?php } ?>

<table class="vertical process">
	<tr>
		<th><?= t('Process')?></th>
		<td>
			<span class="right symbol">
				<a href="../edit_process/<?= $process_id ?>" title="<?= t('edit')?>"></a>
				<a href="../add_connector_to_process/<?= $process_id ?>" title="<?= t('add inflow connector')?>"></a>
				<a href="../add_connector_to_process/<?= $process_id ?>?direction=<?= Connector::DIR_OUT ?>" title="<?= t('add inflow connector')?>"></a>
				<a href="../add_child_for_process/<?= $process_id ?>" title="<?= t('add new child process')?>"></a>
				<a title="<?= t('delete process')?>" href="?action=delete"></a>
			</span>
			<h1><?= $process->base->id ?></h1>
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
	<?php if ($process->base->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= $process->base->description; ?></td>
	</tr>
	<?php } ?>
	<?php if ($process->children()){ ?>
	<tr>
		<th><?= t('Children')?></th>
		<td class="process_children">
			<ul>
			<?php foreach ($process->children() as $child) { ?>
				<li title="<?= $child->base->description ?>">
					<a href="<?= $process_id ?>.<?= $child->id ?>"><?= $child->base->id ?></a>
				</li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
	<?php if ($process->connectors()){ ?>
	<tr>
		<th><?= t('Connectors')?></th>
		<td class="connectors">
			<ul>
			<?php foreach ($process->connectors() as $conn) { ?>
				<li title="<?= $conn->description ?>"><span class="symbol"><?= $conn->base->direction?'':''?></span> <?= $conn->base->id ?></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php';