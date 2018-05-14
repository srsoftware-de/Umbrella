<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$connector_id = param('id2');

$model = Model::load(['ids'=>$model_id]);
$connector = $model->connector_instances($connector_id);
$process = $model->process_instances($connector->process_instance_id);

if ($action = param('action')){
	switch ($action){
		case 'turn':
			$connector->base->turn();
			redirect(getUrl('model',$model_id.'/view'));
			break;
		case 'drop':
			redirect(getUrl('model',$model_id.'/drop_connector_instance/'.$connector_id));
			break;
	}
}

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
				$data['start_connector'] = $connector_id;
				$data['end_connector'] = reset($endpoint); 
				break;
			case Flow::TO_TERMINAL:
				$data['start_terminal'] = reset($endpoint);
				$data['end_connector']  = $connector_id;
				break;
			case Flow::TO_SIBLING:
				$data['start_connector'] = reset($endpoint);
				$data['end_connector'] = $connector_id;
				break;
		}
		$flow = new FlowInstance();
		$base = Flow::load(['project_id'=>$model->project_id,'ids'=>$name]);
		if ($base === null){
			$base = new Flow();			
			$base->patch(['name'=>$name,'project_id'=>$model->project_id,'definition'=>param('definition'),'description'=>param('description')]);			
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
		<?= t('Select terminal or endpoint for flow to connector "?"',$connector->base->id); ?>
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
				<label><input type="radio" name="endpoint" value="<?= Flow::TO_CONNECTOR.':'.$conn->id ?>" /> <?= $conn->base->id ?> (@<?= $conn->angle ?>°)</label>
			</li>
			<?php } ?>
		</ul>
	</fieldset>
	<?php }
	if ($parent = $process->parent_process()) { ?>
	<fieldset>
		<legend>
			<?= t('Siblings') ?>
		</legend>
		<ul>
			<?php foreach ($parent->children() as $sibling){ if ($process->id == $sibling->id) continue; ?>
			<li><?= $sibling->base->id ?>
				<ul>
				<?php foreach ($sibling->connectors() as $conn){ if (!$conn->base->direction) continue; ?>
				<li>
					<label><input type="radio" name="endpoint" value="<?= Flow::TO_SIBLING.':'.$conn->id ?>" /> <?= $conn->base->id ?> (@<?= $conn->angle ?>°)</label>
				</li>
				<?php } // foreach connector ?>
			</ul>
			</li>
			<?php } // foreach sibling ?>
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
	<button type="submit"><?= t('Add flow') ?></button>
	<button type="submit" name="action" value="turn"><?= t('turn connector') ?></button>
	<button type="submit" name="action" value="drop"><?= t('drop connector') ?></button>
</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
