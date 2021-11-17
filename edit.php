<?php include 'controller.php';

require_login('project');

$project_id = param('id');
if (empty($project_id)){
	error('No project id passed!');
	redirect(getUrl('project'));
}
$project = Project::load(['ids'=>$project_id,'users'=>true]);
if (empty($project)){
	error('You are not member of this project!');
	redirect(getUrl('project'));
}

$silent = param('silent',false);

if (post('name')){
	$project->patch($_POST)->save($silent);
	redirect(param('redirect',getUrl('project',$project_id.'/view')));
}

$companies = isset($services['company']) ? request('company','json') : null;

if (isset($services['bookmark'])){
	$hash = sha1(getUrl('project',$project_id.'/view'));
	$bookmark = request('bookmark',$hash.'/json');
}


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset>
		<legend><?= t('Edit Project')?></legend>
		<?php if ($companies) { ?>
		<fieldset>
			<legend><?= t('Company') ?></legend>
			<select name="company_id">
				<option value="0"><?= t('== no company assigned =='); ?></option>
				<?php foreach($companies as $company) { ?>
				<option value="<?= $company['id'] ?>" <?= $company['id'] == $project->company_id ?'selected="true"':''?>><?= $company['name'] ?></option>
				<?php } ?>
			</select>
		</fieldset>
		<?php } ?>
		<fieldset>
			<legend><?= t('Name')?></legend>
			<input type="text" name="name" value="<?= htmlspecialchars($project->name); ?>"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Description – <a target="_blank" href="◊">↗Markdown</a> and <a target="_blank" href="◊">↗PlantUML</a> supported',[t('MARKDOWN_HELP'),t('PLANTUML_HELP')]) ?></legend>
			<textarea id="preview-source" name="description"><?= htmlspecialchars($project->description) ?></textarea>
			<div id="preview"></div>
			
		</fieldset>
		<fieldset class="options">
			<legend><?= t('Options')?></legend>
			<label class="silent_box">
				<input type="checkbox" name="silent" /> <?= t("Don't notify users") ?>
			</label>
		</fieldset>
		<?php if (isset($services['bookmark'])){ ?>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input type="text" name="tags" value="<?= $bookmark ? htmlspecialchars(implode(' ', $bookmark['tags'])) : ''?>" />
		</fieldset>
		<?php } ?>
	<button type="submit"><?= t('Update project') ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
