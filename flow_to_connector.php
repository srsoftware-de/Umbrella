<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$id2 = param('id2');
assert(strpos($id2,'.')!==false,'Parameter does not refer to process.connector');
$id2 = explode('.',$id2);
$process_id = array_shift($id2);
$conn_id = array_shift($id2);

$model = Model::load(['ids'=>$model_id]);
$process = $model->processes($process_id);
$conn = $process->connectors($conn_id);

if ($start = param('start')){
	if (param('name')){
		$start = explode('.',$start);
		$_POST['start_type'] = array_shift($start);
		$_POST['start_id'] = array_shift($start);
		$flow = new Flow();
		$flow->patch($_POST);
		$flow->save();
		redirect(getUrl('model',$model_id.'/view'));
	} else {
		warn('Pleas set at least a name for the flow');
	}
} else {
	warn('Please select starting point for flow');
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
<fieldset>
	<legend>
		<?= t('Select source for flow to connector "?"',$conn->name); ?>
	</legend>
	<label>
		<?= t('Name') ?><input type="text" name="name" />
	</label>
	<label>
		<?= t('Definition') ?><input type="text" name="definition" />
	</label>
	<label>
		<?= t('Description') ?><textarea name="description"></textarea>
	</label>

	<input type="hidden" name="end_type" value="<?= Flow::ENDS_IN_CONNECTOR ?>" />
	<input type="hidden" name="end_id" value="<?= $conn->id ?>" />
	<fieldset>
		<legend>
			<?= t('Terminals') ?>
		</legend>
		<ul>
			<?php foreach ($model->terminals() as $term){ ?>
			<li>
				<label>
					<input type="radio" name="start" value="<?= Flow::ENDS_IN_TERMINAL.'.'.$term->id ?>" />
					<?= $term->name ?>
				</label>
			</li>
			<?php } ?>
		</ul>
	</fieldset>
	<fieldset>
		<legend>
			<?= t('Processes') ?>
		</legend>
		<ul>
			<?php foreach ($model->processes() as $proc){ ?>
			<li><?= $proc->name ?>
				<ul>
					<?php foreach ($proc->connectors() as $conn){ if ($conn->direction) continue; ?>
					<li>
						<label>
							<input type="radio" name="start" value="<?= Flow::ENDS_IN_CONNECTOR.'.'.$conn->id ?>" />
							<?= $conn->name ?>
						</label>
					</li>
					<?php } ?>
				</ul>

			</li>
			<?php } ?>
		</ul>
	</fieldset>
	<button type="submit">
		<?= t('Add flow') ?>
	</button>
</fieldset>
</form>

<?php
debug($_POST);
debug($model);


include '../common_templates/closure.php';