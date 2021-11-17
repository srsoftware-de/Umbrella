<?php include 'controller.php';

require_login('model');

$process_id = param('id');
if (empty($process_id)){
	error('No process id specified!');
	redirect(getUrl('model'));
}

$process = Process::load(['ids'=>$process_id]);
$project = $process->project();
if (empty($project)){
	error('You are not allowed to access that process!');
	redirect(getUrl('model'));
}

if (param('name')){
	$process->patch($_POST)->save();
	redirect(getUrl('model','process/'.$process_id));
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Edit process "◊"',$process->name)?>
		</legend>
		<label>
			<?= t('Name') ?>
			<input type="text" name="name" value="<?= $process->name ?>" />
		</label>
		<label>
			<legend><?= t('Description – <a target="_blank" href="◊">↗Markdown</a> and <a target="_blank" href="◊">↗PlantUML</a> supported',[t('MARKDOWN_HELP'),t('PLANTUML_HELP')]) ?></legend>
			<textarea name="description"><?= $process->description ?></textarea>
		</label>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';