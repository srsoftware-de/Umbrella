<?php include 'controller.php';

require_login('model');

$flow_id = param('id');
if (empty($flow_id)){
	error('No flow id specified!');
	redirect(getUrl('model'));
}

$flow = Flow::load(['ids'=>$flow_id]);
$project = $flow->project();
if (empty($project)){
	error('You are not allowed to access that flow!');
	redirect(getUrl('model'));
}

if ($name = param('name')){
	$flow->patch($_POST)->save();
	redirect(getUrl('model','flow/'.$flow_id));
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend><?= t('Edit flow "?"',$flow->name); ?></legend>
		<fieldset>
			<legend><?= t('Name') ?></legend>
			<input type="text" name="name" value="<?= $flow->name ?>"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Definition') ?></legend>
			<input type="text" name="definition" value="<?= htmlentities($flow->definition) ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="description"><?= $flow->description ?></textarea>
		</fieldset>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';