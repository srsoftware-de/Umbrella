<?php include 'controller.php';

require_login('model');

$process_connector_id = param('id');
if (empty($process_connector_id)) {
	error('No process connector id passed to form!');
	redirect(getUrl('model'));
}

$process_place_id = param('place_id');
if (empty($process_place_id)){
} else {

}
//debug(['prc_con_id'=>$process_connector_id,'prc_plc_id'=>$process_place_id],1);


include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>


<fieldset>
	<legend><?= t('Connector')?></legend>
	<?= t('Name: ?',$connector->name)?><br/>
	<form method="post">
		<fieldset>
			<legend>
				<?= t('Add flow'); ?>
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
</fieldset>


<?php include '../common_templates/closure.php'; ?>
