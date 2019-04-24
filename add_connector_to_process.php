	<?php include 'controller.php';

	require_login('model');

	$process_id = param('id');
	if (empty($process_id)) {
		error('No model id passed to view!');
		redirect(getUrl('model'));
	}

	$process = Process::load(['ids'=>$process_id]);

	if ($name = param('name')){
		$connector = Connector::load(['project_id'=>$process->project_id,'name'=>$name]);
		if (empty($connector)) {
			$connector = new Connector();
			$connector->patch(['project_id'=>$process->project_id,'name'=>$name])->save();
		}
		$process->add($connector);
		redirect(getUrl('model','process/'.$process_id));
	}

	include '../common_templates/head.php';

	include '../common_templates/main_menu.php';
	include '../common_templates/messages.php'; ?>
	<script type="text/javascript" src="<?= getUrl('model','model.js')?>"></script>
	<form method="post">
		<fieldset>
			<legend>
				<?= t('Add connector to process "◊"',$process->name)?>
			</legend>
			<?= t('Connector name') ?>
			<input type="text" name="name" value="<?= param('name',$process->name.':') ?>" autofocus="autofocus" />
			<button type="submit"><?= t('Save'); ?></button>
		</fieldset>
	</form>
	<script type="text/javascript">
	// set cursor to end
		var inp = $('input[name=name]');
		var len = inp.val().length+5;
		inp[0].setSelectionRange(len,len);
	</script>
	<?php include '../common_templates/closure.php';