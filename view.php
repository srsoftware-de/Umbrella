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
include '../common_templates/messages.php'; ?>

<table class="vertical model">
	<tr>
		<th><?= t('Model')?></th>
		<td>
			<h1><?= $model->name ?></h1>
			<span class="right">
				<a title="<?= t('edit')?>"	href="edit"		class="symbol"></a>
				<a title="<?= t('add terminal')?>" href="add_terminal" class="symbol"></a>
			</span>
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
	<?php if ($model->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= $model->description; ?></td>
	</tr>
	<?php } ?>
	
	<?php if ($model->terminals){ ?>
	<tr>
		<th><?= t('Terminals')?></th>
		<td class="terminals"><?php debug($model->terminals) ?></td>
	</tr>
	<?php } ?>
</table>

<?php include '../common_templates/closure.php';