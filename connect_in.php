<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$endpoint_path = param('id2');
assert(strpos($endpoint_path,':')!==false,'Parameter does not refer to process:connector');
$endpoint_path_parts = explode(':',$endpoint_path);
$connector_id = array_pop($endpoint_path_parts); // last part
$process_path = array_pop($endpoint_path_parts);
$process_hierarchy = explode('.',$process_path);

$model = Model::load(['ids'=>$model_id]);
$process = $model->process_instances(array_shift($process_hierarchy));
while(!empty($process_hierarchy)) {
	$child = $process->children(array_shift($process_hierarchy));
	$child->parent = $process;
	$process = $child;
}

$conn = $process->connectors($connector_id);

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
				$target_proc = array_shift($endpoint);
				$target_conn = array_shift($endpoint);
				$data['start_connector'] = $model->id.':'.$process_path;
				$data['start_id']   = $connector_id;
				$data['end_process'] = $model->id.':'.$target_proc; 
				$data['end_id']     = $target_conn;
				break;
			case Flow::TO_TERMINAL:
				$target_term = array_shift($endpoint);
				$data['start_terminal'] = $target_term;
				$data['end_connector']  = $connector_id;
				break;
			case Flow::TO_SIBLING:
				$target_proc = array_shift($endpoint);
				$target_conn = array_shift($endpoint);
				$data['start_process'] = $model->id.':'.$target_proc;
				$data['start_id']   = $target_conn;
				$data['end_process'] = $model->id.':'.$process_path;
				$data['end_id']     = $connector_id;;
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
	warn('Please select starting point for flow');
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
<fieldset>
	<legend>
		<?= t('Select terminal or endpoint for flow to connector "?"',$conn->base->id); ?>
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
			<?php foreach ($child_process->connectors() as $conn){ if ($conn->base->direction) continue; ?>
			<li>
				<label><input type="radio" name="endpoint" value="<?= Flow::TO_CONNECTOR.':'.$process_path.'.'.$child_process->id.':'.$conn->id ?>" /> <?= $conn->base->id ?> (@<?= $conn->angle ?>°)</label>
			</li>
			<?php } ?>
		</ul>
	</fieldset>
	<?php } ?>
	<?php if ($process->parent_process) { 
		$process_path = explode('.', $process_path);
		array_pop($process_path);
		$process_path = implode('.', $process_path);
	?>
	<fieldset>
		<legend>
			<?= t('Siblings') ?>
		</legend>
		<ul>
			<?php foreach ($process->parent->children() as $sibling){				
				if ($process == $sibling) continue; 
			?>
			<li><?= $sibling->name ?>
				<ul>
				<?php foreach ($sibling->connectors() as $conn){ if (!$conn->direction) continue; ?>
				<li>
					<label><input type="radio" name="endpoint" value="<?= Flow::TO_SIBLING.':'.$process_path.'.'.$sibling->id.':'.$conn->id ?>" /> <?= $conn->base->id ?> (@<?= $conn->angle ?>°)</label>
				</li>
				<?php } ?>
			</ul>
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
