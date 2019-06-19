<?php include 'controller.php';

require_login('model');

$diagram_id = param('id');
if (empty($diagram_id)) {
	error('No diagram id passed!');
	redirect(getUrl('model'));
}

$diagram = Diagram::load(['ids'=>$diagram_id]);
if (empty($diagram)){
	error('You are not allowed to access that diagram!');
	redirect(getUrl('model'));
}

if ($name = param('name')){
	$position = param('position',0);
	$phase = new Phase();
	try {
		Phase::shift_positions_from($diagram_id,$position);
		$phase->patch(['diagram_id'=>$diagram_id,'name'=>$name,'description'=>param('description'),'position'=>$position])->save();
		redirect(getUrl('model','diagram/'.$diagram_id.'#phase'.$phase->id));
	} catch (Exception $e){
		error($e);
	}
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
<legend><?= t('Add phase to ◊',$diagram->name); ?></legend>
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

<?php include '../common_templates/closure.php'; ?>