<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

if ($model_id = param('id')){
	$models = [$model_id => Model::load(['ids'=>$model_id])];
} else {
	if ($project_id = param('project')){
		$models = Model::load(['project_id'=>$project_id]);
	} else {
		error('Neither project id nor model id passed!');
		redirect(getUrl('model'));
	}
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

$flows = [];?>

<fieldset>
	<legend><?= t('Project: ?','<a href="'.getUrl('project',$project_id.'/view').'">'.reset($models)->project['name'].'</a>')?></legend>
	<?php foreach ($models as $model) { ?>
	<h2><a href="<?= $model->url() ?>"><?= t('Model: ?',$model->name) ?></a></h2>
	<?= markdown($model->description) ?>
	<h3><?= t('Processes') ?></h3>
	<?php foreach ($model->process_instances() as $process) { ?>
	<fieldset class="export process">
		<h4><a href="<?= $process->url() ?>"><?= t('Process: "?"', $process->base->id) ?></a></h4>
		<?php $process->x = $process->y =  175+$process->base->r; $factor = $process->x / 500; ?>
		<svg style="max-width: 600px"
	 		viewbox="0 0 <?= 1000*$factor ?> <?= 1000*$factor ?>"
	 		onmouseup="click(evt)">
			<script xlink:href="<?= getUrl('model','model.js')?>"></script>
			<rect id='backdrop' x='-10%' y='-10%' width='110%' height='110%' pointer-events='all' />
			<?php
			$null = null; 
			$referenced_terminal_instances = $process->svg($model,$null,['arrows'=>false,'factor'=>1.1]); 
			foreach ($process->connectors() as $conn) {
				$x1 = $process->x + sin($conn->angle*RAD)*$process->base->r ;
				$y1 = $process->y - cos($conn->angle*RAD)*$process->base->r ;
				$x2 = $process->x + sin($conn->angle*RAD)*(100+$process->base->r);
				$y2 = $process->y - cos($conn->angle*RAD)*(100+$process->base->r);
				$flow = reset($conn->flows());
				if ($conn->base->direction){
					arrow($x1,$y1,$x2,$y2, $flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));
					if ($flow->end_terminal){
						$terminal = $model->terminal_instances($flow->end_terminal);
						if ($terminal->base->type){ // Database
							$terminal->x = $x2 - ($x2>$x1 ? 0 : $terminal->base->w);
							$terminal->y = $y2-20;
							$terminal->svg();
						}
					}
				} else {
					arrow($x2,$y2,$x1,$y1, $flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));
					if ($flow->start_terminal) {
						$terminal = $model->terminal_instances($flow->start_terminal);
						if ($terminal->base->type){ // Database
							$terminal->x = $x2 - ($x2>$x1 ? 0 : $terminal->base->w);
							$terminal->y = $y2-20;
							$terminal->svg();
						}
					}
				}
			}
			foreach ($referenced_terminal_instances as $terminal) $terminal->svg(); ?>
		</svg>
		<?= markdown($process->base->description) ?>
		<h4><?= t('Inflows') ?></h4>
		<ul>
			<?php $inflows = []; foreach ($process->connectors() as $connector){
			if ($connector->base->direction) continue; // skip outbound connectors			
			foreach ($connector->flows() as $flow){
				$flows[$flow->base->id] = $flow;
				if ($flow->end_connector != $connector->id) continue; // skip inner flows
				$inflows[$flow->base->id] = $flow;
			} }
			ksort($inflows);
			foreach ($inflows as $id => $flow) {?>
			<li><a href="#<?= $flow->base->id ?>"><?= $flow->base->id ?></a></li>
			<?php }?>
		</ul>

		<h4><?= t('Outflows') ?></h4>
		<ul>
			<?php $outflows = []; foreach ($process->connectors() as $connector){
			if (!$connector->base->direction) continue; // skip outbound connectors
			foreach ($connector->flows() as $flow){
				$flows[$flow->base->id] = $flow;
				if ($flow->start_connector != $connector->id) continue; // skip inner flows
				$outflows[$flow->base->id] = $flow;
			} }
			ksort($outflows);
			foreach ($outflows as $id => $flow) {?>
			<li><a href="#<?= $flow->base->id ?>"><?= $flow->base->id ?></a></li>
			<?php }?>
		</ul>

		<?php if (!empty($databases)) { ?>
		<h3><?= t('Databases') ?></h3>
		<ul>
			<?php foreach ($databases as $db){ ?>
			<li><?= $db->base->id ?></li>
			<?php	}?>
		</ul>
		<?php	} // if databases ?>
	</fieldset>
	<?php } // foreach process ?>
	<?php } // foreach $model ?>
	
	<?php 
	$shown = [];
	$databases = [];
	$terminals = [];
	
	foreach ($models as $model){
		foreach ($model->terminal_instances() as $terminal){
			if (in_array($terminal->base->id,$shown)) continue;
			if ($terminal->isDB()) {
				$databases[$terminal->base->id] = $terminal;
			} else $terminals[$terminal->base->id] = $terminal;
			$shown[] = $terminal->base->id;
		} // foreach terminal
	} // foreach model 
	
	ksort($databases);
	ksort($flows);
	ksort($terminals); ?>
		
	<h2><?= t('Databases'); ?></h2>
	<?php foreach ($databases as $db){ ?>
	<fieldset class="export database">
		<h3><a href="<?= $db->url(); ?>"><?= $db->base->id ?></a></h3>
		<?= markdown($db->base->description) ?>
	</fieldset>
	<?php } // foreach db ?>

	<h2><?= t('Terminals'); ?></h2>
	<?php foreach ($terminals as $terminal){ ?>
	<fieldset class="export terminal">
		<h3><a href="<?= $terminal->url(); ?>"><?= $terminal->base->id ?></a></h3>
		<?= markdown($terminal->base->description) ?>
	</fieldset>
	<?php } // foreach terminal ?>
	
	<h2><?= t('Flows') ?></h2>
	<?php foreach ($flows as $flow) { ?>
	<fieldset class="export flow">
		<h3><a name="<?= $flow->base->id ?>" href="<?= getUrl('model',$flow->model_id.'/flow/'.$flow->id) ?>"><?= $flow->base->id ?></a></h3>	
		<?php if ($flow->base->definition) { echo t('Definition: ?','<code>'.htmlentities($flow->base->definition).'</code>'); } ?>
		<?= markdown($flow->base->description) ?>
	</fieldset>
	<?php } ?>	
	<?php if (isset($services['notes'])) {
		$notes = request('notes','html',['form'=>false,'uri'=>'model:project:'.$project_id],false,NO_CONVERSION);
		if ($notes){ ?>
		<h2><?= t('Notes') ?></h2>
		<?= $notes ?>
		<?php } 
	}?>
</fieldset>
<?php include '../common_templates/closure.php';