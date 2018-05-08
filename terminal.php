<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$terminal_id = param('id2');

if (!$model_id){
	error('No model id passed to terminal.');
	redirect(getUrl('model'));
}
if (!$terminal_id){
	error('No terminal id passed to terminal.');
	redirect(getUrl('model'));
}

$model = Model::load(['ids'=>$model_id]);
$terminal = $model->terminals($terminal_id);

$action = param('action');
if ($action == 'delete' && param('confirm')=='true'){
	$terminal->delete();
	redirect($model->url());
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($action == 'delete'){?>
	<fieldset>
		<legend><?= t('Delete "?"',$terminal->name)?></legend>
		<?= t('You are about to delete the terminal "?". Are you sure you want to proceed?',$terminal->name) ?>
		<a class="button" href="?action=delete&confirm=true"><?= t('Yes')?></a>
		<a class="button" href="?"><?= t('No')?></a>
	</fieldset>
<?php } ?>

<table class="vertical terminal">
	<tr>
		<th><?= t('Terminal')?></th>
		<td>
			<span class="right symbol">
				<a href="../edit_terminal/<?= $terminal->id ?>"></a>
				<a href="?action=delete"></a>
			</span>
			<h1><?= $terminal->name ?></h1>
		</td>
	</tr>
	<tr>
		<th><?= t('Model')?></th>
		<td class="model">
			<a href="<?= getUrl('model',$model->id.'/view'); ?>"><?= $model->name ?></a>
		</td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td class="project">
			<a href="<?= getUrl('project',$model->project_id.'/view'); ?>"><?= $model->project['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$model->project_id ?>" class="symbol" title="show project files" target="_blank"></a>
			</td>
	</tr>
	<?php if ($terminal->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= $terminal->description; ?></td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php';