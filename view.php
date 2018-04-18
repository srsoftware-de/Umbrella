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

<table class="vertical model" style="width: 100%">
	<tr>
		<th><?= t('Model')?></th>
		<td>
			<h1><?= $model->name ?></h1>
			<span class="right">
				<a title="<?= t('edit')?>"	href="edit"		class="symbol"></a>
				<a title="<?= t('add terminal')?>" href="add_terminal" class="symbol"></a>
				<a title="<?= t('add process')?>" href="add_process" class="symbol"></a>
			</span>
		</td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td class="project">
			<a href="<?= getUrl('project',$model->project_id.'/view'); ?>"><?= $model->project['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$model->project_id ?>" class="symbol" title="<?= t('show project files'); ?>" target="_blank"></a>
			</td>
	</tr>
	<?php if ($model->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= $model->description; ?></td>
	</tr>
	<?php } ?>
	<?php if ($model->terminals()){ ?>
	<tr>
		<th><?= t('Terminals')?></th>
		<td class="terminals">
		<?php foreach ($model->terminals() as $terminal){ if ($terminal->type) continue; ?>
		<a class="button" href="terminal/<?= $terminal->id ?>" title="<?= $terminal->description?>"><?= $terminal->name ?></a> 
		<?php } ?>
		</td>
	</tr>
	<tr>
		<th><?= t('Databases')?></th>
		<td class="databases">
		<?php foreach ($model->terminals() as $terminal){ if (!$terminal->type) continue;?>
		<a class="button" href="terminal/<?= $terminal->id ?>" title="<?= $terminal->description ?>">
			<?= $terminal->name ?>
		</a>
		<?php } ?>
		</td>
	</tr>
	<?php } ?>
	<?php if ($model->processes()){ ?>
	<tr>
		<th><?= t('Processes')?></th>
		<td class="processes">
		<?php foreach ($model->processes() as $process){ ?>
		<a class="button" href="process/<?= $process->id ?>" title="<?= $process->description ?>"><?= $process->name ?></a> 
		<?php } ?>
		</td>
	</tr>
	<?php } ?>
	<tr>
		<th><?= t('Display') ?></th>
		<td>
			<svg
				 width="100%"
				 viewbox="0 0 1000 1000"
				 onload="initSVG(evt)"
				 onmousedown="grab(evt)"
				 onmousemove="drag(evt)"
				 onmouseup="drop(evt)"
				 onmousewheel="wheel(evt)">
				<script xlink:href="<?= getUrl('model','model.js')?>"></script>
				<rect id='backdrop' x='-10%' y='-10%' width='110%' height='110%' pointer-events='all' />

				<?php foreach ($model->processes() as $process){
					if (isset($process->parent_process)) continue; // only draw top level processes here. Children are handeled by process->svg();
					foreach ($process->connectors() as $conn){
						foreach ($conn->flows() as $flow){
							if ($flow->start_type == Flow::TO_TERMINAL){
								$terminal = $model->terminals($flow->start_id);
								$x2 = $process->x + sin($conn->angle*RAD)*$process->r;
								$y2 = $process->y - cos($conn->angle*RAD)*$process->r;
								
								$x1 = $terminal->x + $terminal->w/2;
								$y1 = $terminal->y + ($terminal->y > $y2 ? 0 : 30);

								arrow($x1,$y1,$x2,$y2);
							}
							
							if ($flow->end_type == Flow::TO_TERMINAL){
								$terminal = $model->terminals($flow->end_id);							
								$x1 = $process->x + sin($conn->angle*RAD)*$process->r;
								$y1 = $process->y - cos($conn->angle*RAD)*$process->r;
								
								$x2 = $terminal->x + $terminal->w/2;
								$y2 = $terminal->y + ($terminal->y > $y1 ? 0 : 30);
								
								arrow($x1,$y1,$x2,$y2);
							}
								
						 } // foreach flow
					} // foreach connector
				} // foreach process?>

				<?php foreach ($model->processes() as $process){
					$process->svg();
				} // foreach process

				foreach ($model->terminals() as $term){
					$term->svg();
				} // foreach process?>
			</svg>
		</td>
	</tr>
</table>
<?php include '../common_templates/closure.php';