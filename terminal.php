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

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<table class="vertical model">
	<tr>
		<th><?= t('Terminal')?></th>
		<td>
			<h1><?= $terminal->name ?></h1>
			<span class="right">
				<a title="<?= t('edit')?>"	href="edit"		class="symbol"></a>
				<a title="<?= t('add terminal')?>" href="add_terminal" class="symbol"></a>
			</span>
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