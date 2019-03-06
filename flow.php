<?php include 'controller.php';

require_login('model');

$connection_id = param('id');

$flow = Connection::load(['ids'=>$connection_id]);

$action = param('action');
if ($action == 'delete' && param('confirm')=='true'){
	$flow->delete();
	redirect($model->url());
}

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
			<h1><?= $flow->name ?></h1>
			<span class="symbol">
				<a href="../edit_flow/<?= $flow->id ?>" title="<?= t('edit')?>"></a>
				<a href="../turn/<?= $flow->id ?>" title="<?= t('turn')?>"></a>
				<a title="<?= t('delete') ?>" href="?action=delete"></a>
			</span>
		</td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td class="project">
			<a href="<?= getUrl('project',$flow->project['id'].'/view'); ?>"><?= $flow->project['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$flow->project['id'] ?>" class="symbol" title="<?= t('show project files'); ?>" target="_blank"></a>
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