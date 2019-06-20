<?php include 'controller.php';
require_login('task');

if ($key = param('key')){
	$url = getUrl('model');

	$project_ids = [];

	$processes = Process::load(['key'=>$key]);
	if (!empty($processes)){
		foreach ($processes as $process) $project_ids[$process->project_id] = true;
	}

	$terminals = Terminal::load(['key'=>$key]);
	if (!empty($terminals)){
		foreach ($terminals as $terminal) $project_ids[$terminal->project_id] = true;
	}

	$connectors = Connector::load(['key'=>$key]);
	if (!empty($connectors)){
		foreach ($connectors as $connector) $project_ids[$connector->project_id] = true;
	}

	$flows = Flow::load(['key'=>$key]);
	if (!empty($flows)){
		foreach ($flows as $flow) $project_ids[$flow->project_id] = true;
	}

	$diagrams = Diagram::load(['key'=>$key]);
	if (!empty($diagrams)){
		foreach ($diagrams as $diagram) $project_ids[$diagram->project_id] = true;
	}

	$projects = empty($project_ids) ? [] : request('project','json',['ids'=>array_keys($project_ids)]);

	if (!empty($processes)){ ?>
	<fieldset>
		<legend><?= t('Processes')?></legend>
		<table class="process list">
			<tr>
				<th><?= t('Name')?></th>
				<th><?= t('Project')?></th>
			</tr>
			<?php foreach ($processes as $id => $process){ $project = $projects[$process->project_id]; ?>
			<tr class="project<?= $process->project_id ?>">
				<td><a href="<?= $url.$id ?>/view"><?= emphasize($process->name,$key) ?></a></td>
				<td>
					<span class="hover_h">
					<a href="../project/<?= $process->project_id ?>/view"><?= emphasize($project['name'],$key) ?></a>
					</span>
				</td>
			</tr>
			<?php }; // foreach ?>
		</table>
	</fieldset>
	<?php } // processes found*/

	if (!empty($terminals)){ ?>
	<fieldset>
		<legend><?= t('Terminals')?></legend>
		<table class="terminal list">
			<tr>
				<th><?= t('Name')?></th>
				<th><?= t('Project')?></th>
				<th><?= t('Processes')?></th>
			</tr>
			<?php foreach ($terminals as $tid => $terminal){
			$project = $projects[$terminal->project_id]; ?>
			<tr class="project<?= $terminal->project_id ?>">
				<td><a href="<?= $url.'terminal/'.$tid ?>"><?= emphasize($terminal->name,$key) ?></a></td>
				<td><a href="../project/<?= $terminal->project_id ?>/view"><?= emphasize($project['name'],$key) ?></a></td>
				<td><?php foreach ($terminal->occurences() as $pid => $process) { ?>
				<a href="<?= $url.$pid ?>/view"><?= $process->name?></a>
				<?php } ?>
				</td>
			</tr>
		<?php }; // foreach ?>
		</table>
	</fieldset>
	<?php } // terminals found*/


	if (!empty($connectors)){ ?>
	<fieldset>
		<legend><?= t('Connectors')?></legend>
		<table class="connector list">
			<tr>
				<th><?= t('Name')?></th>
				<th><?= t('Project')?></th>
				<th><?= t('Processes')?></th>
			</tr>
			<?php foreach ($connectors as $cid => $connector){
			$project = $projects[$connector->project_id]; ?>
			<tr class="project<?= $connector->project_id ?>">
				<td><?= emphasize($connector->name,$key) ?></td>
				<td><a href="../project/<?= $connector->project_id ?>/view"><?= emphasize($project['name'],$key) ?></a></td>
				<td><?php foreach ($connector->occurences() as $pid => $process) { ?>
				<a href="<?= $url.$pid ?>/view"><?= $process->name?></a>
				<?php } ?>
				</td>
			</tr>
		<?php }; // foreach ?>
		</table>
	</fieldset>
	<?php } // connectors found*/


	if (!empty($flows)){ ?>
	<fieldset>
		<legend><?= t('Flows')?></legend>
		<table class="flow list">
			<tr>
				<th><?= t('Name')?></th>
				<th><?= t('Project')?></th>
				<th><?= t('Processes')?></th>
			</tr>
			<?php foreach ($flows as $fid => $flow){
			$project = $projects[$flow->project_id]; ?>
			<tr class="project<?= $flow->project_id ?>">
				<td><a href="<?= $url.'flow/'.$fid?>"><?= emphasize($flow->name,$key) ?></a></td>
				<td><a href="../project/<?= $flow->project_id ?>/view"><?= emphasize($project['name'],$key) ?></a></td>
				<td><?php foreach ($flow->occurences() as $pid => $process) { ?>
				<a href="<?= $url.$pid ?>/view"><?= $process->name?></a>
				<?php } ?>
				</td>
			</tr>
			<?php }; // foreach ?>
		</table>
	</fieldset>
	<?php } // flows found*/


	if (!empty($diagrams)){ ?>
	<fieldset>
		<legend><?= t('Diagrams')?></legend>
		<table class="diagram list">
			<tr>
				<th><?= t('Name')?></th>
				<th><?= t('Project')?></th>
			</tr>
			<?php foreach ($diagrams as $did => $diagram){
			$project = $projects[$diagram->project_id]; ?>
			<tr class="project<?= $diagram->project_id ?>">
				<td><a href="<?= $url.'diagram/'.$did?>"><?= emphasize($diagram->name,$key) ?></a></td>
				<td><a href="../project/<?= $flow->project_id ?>/view"><?= emphasize($project['name'],$key) ?></a></td>
			</tr>
			<?php }; // foreach ?>
		</table>
	</fieldset>
	<?php } // floas found*/

} // key given