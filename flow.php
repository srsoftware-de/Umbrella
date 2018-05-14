<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

if ($model_id = param('id1')){
	$model = Model::load(['ids'=>$model_id]);
} else {
	error('No model id passed!');
	redirect(getUrl('model'));
}

if ($flow_id = param('id2')){
	$flow = Flow::load(['model_id'=>$model_id,'ids'=>$flow_id]);
} else {
	error('No flow id passed to model/'.$model->id.'/flow!');
	redirect($model->url());
}

$action = param('action');
if ($action == 'delete' && param('confirm')=='true'){
	$flow->delete();
	redirect($model->url());
}

if (file_exists('../lib/parsedown/Parsedown.php')){
	include '../lib/parsedown/Parsedown.php';
	$flow->base->description = Parsedown::instance()->parse(htmlentities($flow->base->description));
} else {
	$flow->base->description = str_replace("\n", "<br/>", htmlentities($flow->base->description));
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; 

if ($action == 'delete'){?>
	<fieldset>
		<legend><?= t('Delete "?"',$flow->base->id)?></legend>
		<?= t('You are about to delete the flow "?". Are you sure you want to proceed?',$flow->base->id) ?>
		<a class="button" href="?action=delete&confirm=true"><?= t('Yes')?></a>
		<a class="button" href="?"><?= t('No')?></a>
	</fieldset>
<?php } ?>


<table class="vertical model" style="width: 100%">
	<tr>
		<th>
			<?= t('Flow')?></th>
		<td>
			<h1><?= $flow->base->id ?></h1>
			<span class="right symbol">
				<a href="../edit_flow/<?= $flow_id ?>" title="<?= t('edit')?>"></a>
				<a title="delete" href="?action=delete"></a>
			</span>
		</td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td class="project">
			<a href="<?= getUrl('project',$model->project_id.'/view'); ?>"><?= $model->project['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$model->project_id ?>" class="symbol" title="<?= t('show project files'); ?>" target="_blank"></a>
			</td>
	</tr>
	<tr>
		<th><?= t('Model')?></th>
		<td class="model">
			<a href="<?= $model->url(); ?>"><?= $model->name ?></a>
		</td>
	</tr>
	<?php if ($flow->base->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= $flow->base->description; ?></td>
	</tr>
	<?php } ?>
	<?php if ($flow->base->definition){ ?>
	<tr>
		<th><?= t('Definition')?></th>
		<td class="definition"><?= htmlentities($flow->base->definition); ?></td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php';