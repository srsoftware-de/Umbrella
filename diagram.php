<?php include 'controller.php';

require_login('model');

$base_url = getUrl('model');

$diagram_id = param('id');
if (empty($diagram_id)) {
	error('No diagram id passed!');
	redirect($base_url);
}

$diagram = Diagram::load(['ids'=>$diagram_id]);
$project = $diagram->project();
if (empty($project)){
	error('You are not allowed to access this diagram!');
	redirect($base_url);
}

$action = param('action');
if ($action == 'delete' && param('confirm')=='true'){
	Diagram::delete($diagram->id);
	redirect($base_url.'?project='.$diagram->project_id);
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($action == 'delete'){?>
	<fieldset>
		<legend><?= t('Delete "◊"',$diagram->name)?></legend>
		<?= t('You are about to delete the diagram "◊". Are you sure you want to proceed?',$diagram->name) ?>
		<a class="button" href="?action=delete&confirm=true"><?= t('Yes')?></a>
		<a class="button" href="?"><?= t('No')?></a>
	</fieldset>
<?php } ?>

<table>
	<tr>
		<th><?= t('Diagram')?></th>
		<td><h1><?= $diagram->name ?></h1></td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td><a href="<?= getUrl('project',$project['id'].'/view')?>"><?= $project['name'] ?></a></td>
	</tr>
	<tr>
		<th><?= t('Description')?></th>
		<td><?= $diagram->description ?></td>
	</tr>
	<tr>
		<th><?= t('Display')?></th>
		<td>
			<?php $party_count = 0; ?>
			<table class="diagram">
				<tr>
					<td class="phase"><?= t('Phases')?><br/><br/></td>
					<?php foreach ($diagram->parties() as $party_id => $party) { $party_count++; ?>
					<th title="<?= $party->description ?>"><?= t($party->name)?></th>
					<?php } ?>
					<td>
						<a class="button" href="<?= getUrl('model','add_party_to_diagram/'.$diagram_id) ?>" title="<?= t('Add a new party to this diagram')?>"><?= t('add party')?></a>
					</td>
				</tr>

				<?php foreach ($diagram->phases() as $phase_id => $phase) { ?>
				<tr>
					<td class="description" rowspan="<?= count($phase->steps()) +2 ?>">
						<a class="button" href="<?= getUrl('model','add_phase_to_diagram/'.$diagram_id.'?position='.$phase->position) ?>" title="<?= t('Add a new phase to this diagram')?>"><?= t('add phase')?></a>
						<p>
						<?= $phase->description ?><br/><br/>
						</p>
					</td>
				</tr>
				<tr>
					<td colspan="<?= $party_count ?>"><h4><?= $phase->name ?></h4></td>
					<td class="actions">
						<a class="button" href="<?= getUrl('model','add_step_to_phase/'.$phase_id) ?>" title="<?= t('Add a new step to this phase')?>"><?= t('add step')?></a>
					</td>
				</tr>
				<?php foreach ($phase->steps() as $step_id => $step) { ?>
				<tr>
					<td colspan="<?= $party_count ?>"><?= t($step->name)?></td>
					<td class="actions">
						<a class="button" href="<?= getUrl('model','add_step_to_phase/'.$phase_id) ?>" title="<?= t('Add a new step to this phase')?>"><?= t('add step')?></a>
					</td>
				</tr>
				<?php }?>
				<?php }?>
				<tr>
					<td>
						<a class="button" href="<?= getUrl('model','add_phase_to_diagram/'.$diagram_id.'?position='.($phase->position+1)) ?>" title="<?= t('Add a new phase to this diagram')?>"><?= t('add phase')?></a>
					</td>
					<td colspan="<?= $party_count +1 ?>"></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?php include '../common_templates/messages.php'; ?>
<?php include '../common_templates/closure.php';