<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

if ($model_id = param('id')){
	$model = Model::load(['ids'=>$model_id]);
} else {
	error('No model id passed!');
	redirect(getUrl('model'));
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<h1><a href="<?= $model->url() ?>"><?= t('Model: ?',$model->name) ?></a></h1>
<?= markdown($model->description) ?>

<h2><?= t('Processes') ?></h2>
<?php foreach ($model->process_instances() as $process) { ?>
<h3><a href="<?= $process->url() ?>"><?= t('Process: "?"', $process->base->id) ?></a></h3>
<?php $process->x = $process->y =  175+$process->base->r;
	$factor = $process->x / 500;
?>
<svg style="max-width: 600px"
	 viewbox="0 0 <?= 1000*$factor ?> <?= 1000*$factor ?>"
	 onmouseup="c(evt)">
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
	foreach ($referenced_terminal_instances as $terminal) $terminal->svg();
?>
</svg>
<?= markdown($process->base->description) ?>
<h4><?= t('Inflows') ?></h4>
<ul>
	<?php foreach ($process->connectors() as $connector){
	if ($connector->base->direction) continue; // skip outbound connectors
	foreach ($connector->flows() as $flow){
		if ($flow->end_connector != $connector->id) continue; // skip inner flows ?>
	<li><?= $flow->base->id ?></li>
	<?php } } ?>
</ul>

<h3><?= t('Outflows') ?></h3>
<ul>
	<?php foreach ($process->connectors() as $connector){
	if (!$connector->base->direction) continue; // skip outbound connectors
	foreach ($connector->flows() as $flow){
		if ($flow->start_connector != $connector->id) continue; // skip inner flows ?>
	<li><?= $flow->base->id ?></li>
	<?php } } ?>
</ul>

<?php if (!empty($databases)) { ?>
<h3><?= t('Databases') ?></h3>
<ul>
	<?php foreach ($databases as $db){ ?>
	<li><?= $db->base->id ?></li>
	<?php }?>
</ul>
<?php } // if databases ?>
<?php } // foreach process?>

<h2><?= t('Databases'); ?></h2><?php
$shown = [];
foreach ($model->terminal_instances() as $terminal){
	if (!$terminal->isDB() || in_array($terminal->base->id,$shown)) continue; ?>
	<h3><a href="<?= $terminal->url(); ?>"><?= $terminal->base->id ?></a></h3>
	<?= markdown($terminal->base->description) ?>
	<?php
	$shown[] = $terminal->base->id;
} // foreach terminal ?>

<h2><?= t('Terminals'); ?></h2><?php
$shown = [];
foreach ($model->terminal_instances() as $terminal){
	if ($terminal->isDB() || in_array($terminal->base->id,$shown)) continue; ?>
	<h3><a href="<?= $terminal->url(); ?>"><?= $terminal->base->id ?></a></h3>
	<?= markdown($terminal->base->description) ?>
	<?php
	$shown[] = $terminal->base->id;
} // foreach terminal ?>

<?php include '../common_templates/closure.php';