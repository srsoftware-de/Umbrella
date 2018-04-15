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

$rad = 0.01745329;

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
				 onload="Init(evt)"
				 onmousedown="Grab(evt)"
				 onmousemove="Drag(evt)"
				 onmouseup="Drop(evt)"
				 onmousewheel="Wheel(evt)">
				<script xlink:href="<?= getUrl('model','model.js')?>"></script>
				<rect id='backdrop' x='-10%' y='-10%' width='110%' height='110%' pointer-events='all' />

				<?php foreach ($model->processes() as $process){ ?>

					<?php foreach ($process->connectors() as $conn){ ?>

					<?php foreach ($conn->flows() as $flow){
						$x1 = 0;
						$y1 = 0;
						if ($flow->start_type == Flow::ENDS_IN_TERMINAL){
							$terminal = $model->terminals($flow->start_id);
							$x1 = $terminal->x + $terminal->w/2;
							$y1 = $terminal->y + 15;
						}
						$x2 = $process->x + sin($conn->angle*$rad)*$process->r;
						$y2 = $process->y - cos($conn->angle*$rad)*$process->r;
					?>
					  <line
							x1="<?= $x1 ?>"
							y1="<?= $y1 ?>"
							x2="<?= $x2 ?>"
							y2="<?= $y2 ?>"
							style="stroke:rgb(255,0,0);stroke-width:2" />

					<?php } // foreach flow
					} // foreach connector
				} // foreach process?>

				<?php foreach ($model->processes() as $process){ ?>
				<g>
					<circle
							class="process"
							cx="<?= $process->x ?>"
							cy="<?= $process->y ?>"
							r="<?= $process->r?>"
							id="process_<?= $process->id ?>">
						<title><?= $process->description ?></title>
					</circle>
					<text x="<?= $process->x ?>" y="<?= $process->y ?>" fill="red"><?= $process->name ?></text>
					<?php foreach ($process->connectors() as $conn){ ?>
					<a xlink:href="flow_to_connector/<?= $process->id ?>.<?= $conn->id ?>">
						<circle
								class="connector"
								cx="<?= $process->x ?>"
								cy="<?= $process->y - $process->r ?>"
								r="10"
								id="connector_<?= $process->id ?>.<?= $conn->id ?>"
								transform="rotate(<?= $conn->angle ?>,<?= $process->x ?>,<?= $process->y ?>)">
							<title><?= $conn->name ?></title>
						</circle>
					</a>

					<?php } // foreach connector ?>
				</g>
				<?php } // foreach process?>

				<?php foreach ($model->terminals() as $term){ ?>
				<g>
					<?php if (!$term->type) { ?>
					<rect
							class="terminal"
							x="<?= $term->x?>"
							y="<?= $term->y?>"
							width="<?= $term->w ?>"
							height="30"
							id="terminal_<?= $term->id ?>">
						<title><?= $term->description ?></title>
					</rect>
					<text x="<?= $term->x + $term->w/2 ?>" y="<?= $term->y + 15 ?>" fill="red"><?= $term->name ?></text>
					<?php } else { ?>
					<ellipse cx="<?= $term->x + $term->w/2 ?>" cy="<?= $term->y + 40 ?>" rx="<?= $term->w/2?>" ry="15" />
					<rect
							class="terminal"
							x="<?= $term->x?>"
							y="<?= $term->y ?>"
							width="<?= $term->w ?>"
							height="40"
						  	stroke-dasharray="0,<?= $term->w ?>,40,<?= $term->w ?>,40"
							id="terminal_<?= $term->id ?>">
						<title><?= $term->description ?></title>
					</rect>
					<ellipse cx="<?= $term->x + $term->w/2 ?>" cy="<?= $term->y ?>" rx="<?= $term->w/2?>" ry="15" />//-->
					<text x="<?= $term->x + $term->w/2 ?>" y="<?= $term->y + 30 ?>" fill="red"><?= $term->name ?></text>
					<?php } ?>

				</g>
				<?php } // foreach process?>
			</svg>
		</td>
	</tr>
</table>
<?php
debug($model);
include '../common_templates/closure.php';