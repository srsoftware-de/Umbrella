<?php include 'controller.php';

require_login('model');

$base_url = getUrl('model');

$terminal_id = param('id');
if (empty($terminal_id)){
	error('No terminal id specified!');
	redirect($base_url);
}

$terminal = Terminal::load(['ids'=>$terminal_id]);
$project = $terminal->project();
if (empty($project)){
	error('You are not allowed to access that terminal!');
	redirect($base_url);
}

$action = param('action');
if ($action == 'delete' && param('confirm')=='true'){
	$terminal->delete();
	redirect($base_url);
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
		<th><?= t($terminal->type==Terminal::TERMINAL?'Terminal':'Database')?></th>
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
	<?php }
	if (!empty($terminal->occurences())) { ?>
	<tr>
		<th><?= t('Occurences')?></th>
		<td class="occurences">
			<?php
			foreach ($terminal->occurences() as $proc){ ?>
				<a class="button" href="<?= $base_url.'process/'.$proc->id ?>"><?= $proc->name ?></a>
			<?php }?>
		</td>
	</tr>
	<?php } ?>
</table>

		<?php if (!empty($terminal->fields())) {?>
		<fieldset>
			<legend><?= t('Fields')?></legend>
			<table>
				<tr>
					<td colspan="3"></td>
					<th colspan="2">Beschränkungen</th>
					<td></td>
				</tr>
				<tr>
					<th><?= t('field') ?></th>
					<th><?= t('type') ?></th>
					<th>NOT NULL</th>
					<th><?= t('DEFAULT') ?></th>
					<th><?= t('Key') ?></th>
					<th><?= t('reference') ?></th>
				</tr>
				<?php foreach ($terminal->fields() as $field){ ?>
				<tr>
					<td><?= $field['name']?></td>
					<td><?= $field['type']?></td>
					<td><?= $field['not_null']?'✓':''?></td>
					<td><?= $field['default_val']?></td>
					<td><?= $field['key_type']=='P'?'PRIMARY':($field['key_type']=='U'?'UNIQUE':$field['key_type'])?></td>
					<td><?php if ($field['reference']!='NULL') { $ref = Terminal::field($field['reference'])?>
					<a href="<?= getUrl('model','terminal/'.$ref['id'])?>"><?= $ref['tName'].'.'.$ref['fName']?></a>
					<?php }?>
					</td>
				</tr>
				<?php } ?>
			</table>
		</fieldset>
		<?php } // terminal is DB?>

<?php include '../common_templates/closure.php';