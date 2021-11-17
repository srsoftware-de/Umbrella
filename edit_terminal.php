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
	error('You are not allowed to access tdat terminal!');
	redirect(getUrl('model'));
}

if (param('name')){
	$terminal->patch($_POST)->save();
	if (empty($terminal->new_field['name'])) redirect(getUrl('model','terminal/'.$terminal_id));
	unset($_POST);
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Edit terminal "◊"',$terminal->name)?>
		</legend>
		<fieldset>
			<legend><?= t('Name') ?></legend>
			<input type="text" name="name" value="<?= $terminal->name ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description – <a target="_blank" href="◊">↗Markdown</a> and <a target="_blank" href="◊">↗PlantUML</a> supported',[t('MARKDOWN_HELP'),t('PLANTUML_HELP')]) ?></legend>
			<textarea name="description"><?= htmlspecialchars($terminal->description) ?></textarea>
		</fieldset>
		<?php if ($terminal->isDB()) { $project_terminals = Terminal::load(['project_id'=>$project['id']]);?>
		<fieldset>
			<legend><?= t('Fields')?></legend>
			<table>
				<tr>
					<td colspan="2"></td>
					<td colspan="3">Beschränkungen<hr/>
					</td>
					<td></td>
					<td></td>
				</tr>
				<tr>
					<th><?= t('field') ?></th>
					<th><?= t('type') ?></th>
					<th>NOT NULL</th>
					<th><?= t('DEFAULT') ?></th>
					<th><?= t('Key') ?></th>
					<th><?= t('Reference') ?></th>
					<th><?= t('Description') ?></th>
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
					<td><?= markdown($field['description']) ?></td>
				</tr>
				<?php } ?>
				<tr>
					<td><input type="text" name="new_field[name]" value=""></td>
					<td><input type="text" name="new_field[type]" value="INT"></td>
					<td><input type="checkbox" name="new_field[not_null]" checked="checked"/></td>
					<td><input type="text" name="new_field[default_val]"/></td>
					<td>
						<select name="new_field[key_type]">
							<option value="">----</option>
							<option value="P">PRIMARY KEY</option>
							<option value="U">UNIQUE</option>
						</select>
					</td>
					<td>
						<select name="new_field[reference]">
							<option value="">----</option>
							<?php foreach ($project_terminals as $term){
								foreach ($term->fields() as $fid => $f){ if (empty($f['key_type'])) continue;?>
							<option value="<?= $fid ?>"><?= $term->name.'.'.$f['name']?></option>
							<?php }}?>
						</select>
					</td>
					<td>
						<textarea name="new_field[description]"></textarea>
					</td>
				</tr>
			</table>
		</fieldset>
		<?php } // terminal is DB?>
		<button type="submit"><?= t('Save'); ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';