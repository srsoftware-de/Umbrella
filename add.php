<?php $title = 'Umbrella Project Management';

include '../bootstrap.php';
include 'controller.php';

require_login('project');
if ($name = post('name')){
	$project = add_project($name,post('description'),post('company'));
	if (param('from') == 'task') die(json_encode($project)); // used for task-to-project conversion
	redirect(getUrl('project',$project['id'].'/view'));
}

$companies = isset($services['company']) ? request('company','json') : null;

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend><?= t('Create new project')?></legend>
		<?php if ($companies) { ?>
		<fieldset>
			<legend><?= t('Company')?></legend>
			<select name="company">
				<option value=""><?= t('no company')?></option>
			<?php foreach($companies as $company) { ?>
				<option value="<?= $company['id'] ?>"><?= $company['name'] ?></a>
			<?php } ?>
			</select>
		</fieldset>
		<?php } // if companies ?>
		<fieldset>
			<legend><?= t('Name')?></legend>
			<input type="text" name="name" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="description"></textarea>
		</fieldset>
		<?php if (isset($services['bookmark'])){?>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input name="tags" type="text" value="" />
		</fieldset>
		<?php }?>
		<button type="submit"><?= t('Create new project')?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
