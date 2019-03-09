<?php include 'controller.php';

require_login('model');

$terminal_id = param('id');
if (empty($terminal_id)){
	error('No terminal id specified!');
	redirect(getUrl('model'));
}

$terminal = Terminal::load(['ids'=>$terminal_id]);
$project = $terminal->project();
if (empty($project)){
	error('You are not allowed to access that terminal!');
	redirect(getUrl('model'));
}

$action = param('action');
if ($action == 'delete' && param('confirm')=='true'){
	$terminal->delete();
	redirect(getUrl('model'));
}

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
				<a href="<?= getUrl('model','edit_terminal/'.$terminal->id) ?>" title="<?= t('edit terminal') ?>"></a>
				<a href="?action=delete" title="<?= t('delete terminal') ?>"></a>
			</span>
			<h1><?= $terminal->name ?></h1>
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
	<?php if ($terminal->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= markdown($terminal->description); ?></td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php';