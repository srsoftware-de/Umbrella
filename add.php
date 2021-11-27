<?php include 'controller.php';

require_login('project');

if ($name = post('name')){
    $_POST['show_closed'] = $_POST['show_closed'] == 'on' ? 1 : 0;
	$project = new Project();
	$project->patch($_POST)->save()->addUser(['id'=>$user->id,'email'=>$user->email],PROJECT_PERMISSION_OWNER);
	if (param('from') == 'task') die(json_encode($project)); // used for task-to-project conversion
	if ($status = param('status')){ // for import
		die('NEW_PROJECT_ID='.$project->id);
	} else redirect(getUrl('project',$project->id.'/view'));
}

$companies = isset($services['company']) ? request('company','json') : null;

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend><?= t('Create new project')?></legend>
		<?php if ($companies) { ?>
		<fieldset>
			<legend><?= t('Company')?></legend>
			<select name="company_id">
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
			<legend><?= t('Description – <a target="_blank" href="◊">↗Markdown</a> and <a target="_blank" href="◊">↗PlantUML</a> supported',[t('MARKDOWN_HELP'),t('PLANTUML_HELP')]) ?></legend>
			<textarea id="preview-source" name="description"><?= param('description'); ?></textarea>
			<div id="preview"></div>
		</fieldset>
		<?php if (isset($services['bookmark'])){?>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input name="tags" type="text" value="<?= param('tags')?>" />
		</fieldset>
		<?php }?>
		<fieldset>
			<legend><?= t('Options')?></legend>
			<label>
				<input type="checkbox" name="show_closed" />
				<?= t('Always display closed tasks')?>
			</label>
		</fieldset>
		
		<button type="submit"><?= t('Create new project')?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
