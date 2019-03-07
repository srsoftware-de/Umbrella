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
		redirect('model','process/'.$process_id);
	}

	include '../common_templates/head.php';

	include '../common_templates/main_menu.php';
	include '../common_templates/messages.php'; ?>
	<script type="text/javascript" src="<?= getUrl('model','model.js')?>"></script>
	<form method="post">
		<fieldset>
			<legend>
				<?= t('Add connector to process "?"',$process->name)?>
			</legend>
			<?= t('Connector name') ?>
			<input type="text" name="name" value="" autofocus="autofocus" />
			<button type="submit"><?= t('Save'); ?></button>
		</fieldset>
	</form>

	<?php include '../common_templates/closure.php';