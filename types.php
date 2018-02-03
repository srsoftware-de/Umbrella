<?php

include '../bootstrap.php';
include 'controller.php';

$title = t('Umbrella: Document Management');
require_login('document');

$docTypes = DocumentType::load();

if ($update = param('update')){
	switch ($update){
		case 'new':
			$newType = new DocumentType();
			$newType->patch(['name'=>param('name'),'next_type_id'=>param('next_type_id')]);
			$newType->save();
			$docTypes = DocumentType::load();
			break;
		default:
			$successors = param('successors');
			$docType = $docTypes[$update];
			$docType->patch($successors[$update]);
			$docType->save();
	}
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Document types') ?></legend>
	<form method="POST">
		<table>
			<tr>
				<th><?= t('Type')?></th>
				<th><?= t('Successor') ?></th>
				<th><?= t('Actions') ?></th>
			</tr>
			<?php foreach ($docTypes as $id => $dt){ ?>
			<tr>
				<td><?= t($dt->name) ?></td>
				<td>
					<select name="successors[<?= $id ?>][next_type_id]">
						<?php foreach($docTypes as $sid => $ndt){ ?>
						<option value="<?= $sid ?>" <?= $sid == $dt->next_type_id ? 'selected="true"':'' ?>><?= t($ndt->name) ?></option>
						<?php } ?>
					</select>
				</td>
				<td>
					<button type="submit" name="update" value="<?= $id ?>">
						<?= t('Update') ?>
					</button>
				</td>
			</tr>
			<?php } ?>
			<tr>
				<td><input type="text" name="name"></td>
				<td>
					<select name="next_type_id">
						<?php foreach($docTypes as $id => $dt){ ?>
						<option value="<?= $id ?>"><?= t($dt->name) ?></option>
						<?php } ?>
					</select>
				</td>
				<td>
					<button type="submit" name="update" value="new">
						<?= t('Save new doc type') ?>
					</button>
				</td>
			</tr>
		</table>
	</form>
</fieldset>

<?php include 'common_templates/closure.php'; ?>