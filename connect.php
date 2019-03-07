<?php include 'controller.php';

require_login('model');
debug('connect',1);
$process_id = param('id');
if (empty($process_id)) {
	error('No model id passed to form!');
	redirect(getUrl('model'));
}

$process = Process::load(['ids'=>$process_id]);
if (empty($process)){
	error('You don`t have access to that process');
	redirect(getUrl('model'));
}

$connector_id = param('connector');
if (empty($connector_id)){
	error('No connector id passed to form!');
	redirect(getUrl('model'));
}

$connector = Connector::load(['ids'=>$connector_id]);


if ($name = param('name')){
	$flow = Flow::load(['ids'=>$process->project['id'].':'.$name]);
	if (empty($flow)){
		$flow = new Flow();
		$flow->patch(['name'=>$name,'project'=>$process->project])->patch($_POST)->save();

	}
	$connection = new Flow();

	$parts = explode(':',param('endpoint'),2);
	$endpoint_type = $parts[0];
	$endpoint_id = $parts[1];

	$connection->patch(['flow_id'=>$flow->id(),'start_connector'=>$connector->id(),'end_'.$endpoint_type=>$endpoint_id])->save();
	redirect(getUrl('model',$process_id.'/view'));
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
<fieldset>
	<legend>
		<?= t('Add flow from connector "?"',$connector->name); ?>
	</legend>
	<fieldset>
		<legend><?= t('Name') ?></legend>
		<input type="text" name="name" value="<?= param('name','')?>"/>
	</fieldset>
	<fieldset>
		<legend><?= t('Definition') ?></legend>
		<input type="text" name="definition" value="<?= param('definition','')?>"/>
	</fieldset>
	<fieldset>
		<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
		<textarea name="description"><?= param('description','') ?></textarea>
	</fieldset>

	<?php if ($process->connectors()){ ?>
	<fieldset>
		<legend>
			<?= t('Connectors of ?',$process->name) ?>
		</legend>
		<ul>
			<?php foreach ($process->connectors() as $conn){ if ($conn->id() == $connector_id) continue; ?>
			<li>
				<label><input type="radio" name="endpoint" value="connector:<?= $conn->id() ?>" /> <?= $conn->name ?> (@<?= $conn->angle ?>°)</label>
			</li>
			<?php } ?>
		</ul>
	</fieldset>
	<?php } ?>

	<?php if ($process->children()) foreach ($process->children() as $child_process){ ?>
	<fieldset>
		<legend>
			<?= t('Child process: ?',$child_process->name) ?>
		</legend>
		<ul>
			<?php foreach ($child_process->connectors() as $conn){ if ($conn->id() == $connector_id) continue; ?>
			<li>
				<label><input type="radio" name="endpoint" value="connector:<?= $conn->id() ?>" /> <?= $conn->name ?> (@<?= $conn->angle ?>°)</label>
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
			<?php foreach ($process->terminals() as $term){ ?>
			<li>
				<label>
					<input type="radio" name="endpoint" value="terminal:<?= $term->id() ?>" />
					<?= $term->name ?> @ (<?= $term->x ?>, <?= $term->y ?>)
				</label>
			</li>
			<?php } ?>
		</ul>
	</fieldset>
	<button type="submit"><?= t('Add flow') ?></button>
	<button type="submit" name="action" value="drop"><?= t('drop connector') ?></button>
</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
