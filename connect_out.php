<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$connector_id = param('id2');

$model = Model::load(['ids'=>$model_id]);
$connector = $model->connector_instances($connector_id);
$process = Process::load(['model_id'=>$model_id,'ids'=>$connector->process_instance_id]);


if ($endpoint = param('endpoint')){
	if ($name = param('name')){
		$endpoint = explode(':', $endpoint);
		$endpoint_type = array_shift($endpoint);
		$data = [
			'model_id'=>$model_id,
			'flow_id'=>$name,
			'start_connector'=>null,
			'start_terminal'=>null,
			'end_connector'=>null,
			'end_terminal'=>null,
		];
		switch ($endpoint_type){
			case Flow::TO_CONNECTOR:
				$data['start_connector'] = reset($endpoint);
				$data['end_connector'] = $connector_id;
				break;
			case Flow::TO_TERMINAL:
				$target_term = array_shift($endpoint);
				$data['start_connector']  = $connector_id;
				$data['end_terminal'] = $target_term;
break;
		}
		$flow = new Flow();
		$base = FlowBase::load(['model_id'=>$model_id,'ids'=>$name]);
		if ($base === null){
			$base = new FlowBase();
			$base->patch(['name'=>$name,'model_id'=>$model_id,'definition'=>param('definition'),'description'=>param('description')]);
			$base->save();
		}
		$flow->base = $base;
		$flow->patch($data);
		$flow->save();
		redirect($model->url());
	} else {
		warn('Pleas set at least a name for the flow');
	}
} else {
	warn('Please select end point for flow');
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
<fieldset>
	<legend>
		<?= t('Select terminal or endpoint for flow from connector "?"',$connector->base->id); ?>
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

	<?php if ($process->children()) foreach ($process->children() as $child_process){ ?>
	<fieldset>
		<legend>
			<?= t('Child process: ?',$child_process->base->id) ?>
		</legend>
		<ul>
			<?php foreach ($child_process->connectors() as $conn){ if (!$conn->base->direction) continue; ?>
			<li>
				<label><input type="radio" name="endpoint" value="<?= Flow::TO_CONNECTOR.':'.$conn->id ?>" /> <?= $conn->base->id ?> (@<?= $conn->angle ?>°)</label>
			</li>
			<?php } ?>
		</ul>
	</fieldset>
	<?php } ?>
	<fieldset>
		<legend>
			<?= t('Terminals') ?>
		</legend>
		<ul>
			<?php foreach ($model->terminal_instances() as $term){ ?>
			<li>
				<label>
					<input type="radio" name="endpoint" value="<?= Flow::TO_TERMINAL.':'.$term->id ?>" />
					<?= $term->base->id ?> @ (<?= $term->x ?>, <?= $term->y ?>)
				</label>
			</li>
			<?php } ?>
		</ul>
	</fieldset>
	<button type="submit">
		<?= t('Add flow') ?>
	</button>
</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
