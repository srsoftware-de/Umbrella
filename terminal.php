<?php include 'controller.php';

require_login('model');

$model_id = param('id1');
$terminal_instance_id = param('id2');

if (!$model_id){
	error('No model id passed to terminal.');
	redirect(getUrl('model'));
}
if (!$terminal_instance_id){
	error('No terminal id passed to terminal.');
	redirect(getUrl('model'));
}

$model = Model::load(['ids'=>$model_id]);
$terminal = $model->terminal_instances($terminal_instance_id);

$action = param('action');
if ($action == 'delete' && param('confirm')=='true'){
	$terminal->delete();
	redirect($model->url());
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($action == 'delete'){?>
	<fieldset>
		<legend><?= t('Delete "?"',$terminal->base->id)?></legend>
		<?= t('You are about to delete the terminal "?". Are you sure you want to proceed?',$terminal->base->id) ?>
		<a class="button" href="?action=delete&confirm=true"><?= t('Yes')?></a>
		<a class="button" href="?"><?= t('No')?></a>
	</fieldset>
<?php } ?>

<table class="vertical terminal">
	<tr>
		<th><?= t('Terminal')?></th>
		<td>
			<span class="right symbol">
				<a href="../edit_terminal/<?= $terminal->id ?>" title="<?= t('edit terminal') ?>"></a>
				<a href="?action=delete" title="<?= t('delete terminal') ?>"></a>
			</span>
			<h1><?= $terminal->base->id ?></h1>
		</td>
	</tr>
	<tr>
		<th><?= t('Model')?></th>
		<td class="model">
			<a href="<?= getUrl('model',$model->id.'/view'); ?>"><?= $model->name ?></a>
			<a class="symbol" title="<?= t('show other models') ?>"   href="<?= getUrl('model').'?project='.$terminal->base->project_id ?>"></a>
		</td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td class="project">
			<a href="<?= getUrl('project',$model->project_id.'/view'); ?>"><?= $model->project['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a class="symbol" title="show project files"  href="<?= getUrl('files').'?path=project/'.$model->project_id ?>"target="_blank"></a>
			</td>
	</tr>
	<?php if ($terminal->base->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= markdown($terminal->base->description); ?></td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php';