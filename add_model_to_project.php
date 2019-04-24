<?php include 'controller.php';

require_login('model');

$project_id = param('id');
if (empty($project_id)){
	error('No project id passed!');
	redirect(getUrl('model'));
}

$project = request('project','json',['ids'=>$project_id]);
if (empty($project)){
	error('You are not allowed to access that project!');
	redirect(getUrl('model'));
}

if ($name = param('name')){
	$model = new Process();
	try {
		$model->patch(['project_id'=>$project_id,'name'=>$name,'description'=>param('description'),'r'=>NULL])->save();
		redirect(getUrl('model','model/'.$model->id));
	} catch (Exception $e){
		error($e);
	}
}


include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add Model to ?',$project['name']); ?></legend>
	<form method="POST">
	<fieldset>
		<legend><?= t('Name'); ?></legend>
		<input type="text" name="name" value="<?= param('name','') ?>" />
	</fieldset>
	<fieldset>
		<legend><?= t('Description - <a target="_blank" href="◊">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
		<textarea name="description"><?= param('description','') ?></textarea>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php';
