<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$id2 = param('id2');
assert(strpos($id2,'.')!==false,'Parameter does not refer to process.connector');
$process_hierarchy = explode('.',$id2);
$conn_id = array_pop($process_hierarchy); // last part

$model = Model::load(['ids'=>$model_id]);
$process = $model->processes(array_shift($process_hierarchy));
while(!empty($process_hierarchy)) {
	$child = $process->children(array_shift($process_hierarchy));
	$child->parent = $process;
	$process = $child;
}

$conn = $process->connectors($conn_id);

if ($endpoint = param('endpoint')){
	if ($name = param('name')){
		$endpoint = explode('.', $endpoint);
		$endpoint_type = array_shift($endpoint);
		$endpoint_id = array_shift($endpoint);
		
		$data = [
			'name'=>$name,
			'definition'=>param('definition'),
			'description'=>param('description'),
			'end_type'   =>Flow::TO_CONNECTOR,
		];
		
		switch ($endpoint_type){
			case Flow::TO_CONNECTOR:
				$data['start_type'] = Flow::TO_CONNECTOR;
				$data['start_id']   = $conn_id;
				$data['end_id']     = $endpoint_id;
				break;
			case Flow::TO_TERMINAL:
				$data['start_type'] = Flow::TO_TERMINAL;
				$data['start_id']   = $endpoint_id;
				$data['end_id']     = $conn_id;
				break;
		}
		
		$flow = new Flow();
		$flow->patch($data);
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

	<?php if ($process->children()) foreach ($process->children() as $child){ ?>
	<fieldset>
		<legend>
			<?= t('Child process: ?',$child->name) ?>
		</legend>
		<ul>
			<?php foreach ($child->connectors() as $conn){ if (!$conn->direction) continue; ?>
			<li>
				<label>
					<input type="radio" name="endpoint" value="<?= Flow::TO_CONNECTOR.'.'.$conn->id ?>" />
					<?= $conn->name ?>
				</label>
			</li>
			<?php } ?>
		</ul>
	</fieldset>
	<?php } ?>
	<?php if (!$process->parent_process) { ?>
	<fieldset>
		<legend>
			<?= t('Terminals') ?>
		</legend>
		<ul>
			<?php foreach ($model->terminals() as $term){ ?>
			<li>
				<label>
					<input type="radio" name="endpoint" value="<?= Flow::TO_TERMINAL.'.'.$term->id ?>" />
					<?= $term->name ?>
				</label>
			</li>
			<?php } ?>
		</ul>
	</fieldset>
	<?php } ?>
	<button type="submit">
		<?= t('Add flow') ?>
	</button>
</fieldset>
</form>

<?php
debug(['prc'=>$process,'model'=>$model,'con'=>$conn]);

include '../common_templates/closure.php';
