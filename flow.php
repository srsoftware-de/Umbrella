<?php include 'controller.php';

require_login('model');

$flow_id = param('id');
if (empty($flow_id)){
	error('No flow id specified!');
	redirect(getUrl('model'));
}

$flow = Flow::load(['ids'=>$flow_id]);
$project = $flow->project();
if (empty($project)){
	error('You are not allowed to access that flow!');
	redirect(getUrl('model'));
}

$action = param('action');
if ($action == 'delete' && param('confirm')=='true'){
	$flow->delete();
	redirect(getUrl('model'));
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($action == 'delete'){?>
	<fieldset>
		<legend><?= t('Delete "?"',$flow->name)?></legend>
		<?= t('You are about to delete the flow "?". Are you sure you want to proceed?',$flow->name) ?>
		<a class="button" href="?action=delete&confirm=true"><?= t('Yes')?></a>
		<a class="button" href="?"><?= t('No')?></a>
	</fieldset>
<?php } ?>


<table class="vertical model" style="width: 100%">
	<tr>
		<th>
			<?= t('Flow')?></th>
		<td>
			<h1><?= $flow->name ?></h1>
			<span class="symbol">
				<a href="../edit_flow/<?= $flow->id ?>" title="<?= t('edit')?>"></a>
				<a title="<?= t('delete') ?>" href="?action=delete"></a>
			</span>
		</td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td class="project">
			<a href="<?= getUrl('project',$project['id'].'/view'); ?>"><?= $project['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$project['id'] ?>" class="symbol" title="<?= t('show project files'); ?>" target="_blank"></a>
			<a class="symbol" title="<?= t('show other models') ?>"   href="<?= getUrl('model').'?project='.$project['id'] ?>"></a>
			</td>
	</tr>
	<?php if ($flow->definition){ ?>
	<tr>
		<th><?= t('Definition')?></th>
		<td class="definition"><?= htmlentities($flow->definition); ?></td>
	</tr>
	<?php } ?>
	<?php if ($flow->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= markdown($flow->description); ?></td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php';