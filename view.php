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
				 onload="Init(evt)"
				 onmousedown="Grab(evt)"
				 onmousemove="Drag(evt)"
				 onmouseup="Drop(evt)"
				 onmousewheel="Wheel(evt)">
				<script xlink:href="<?= getUrl('model','model.js')?>"></script>
				<rect id='backdrop' x='-10%' y='-10%' width='110%' height='110%' pointer-events='all' />

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
					<circle
							class="connector"
							cx="<?= $process->x ?>"
							cy="<?= $process->y - $process->r ?>"
							r="10"
							id="connector_<?= $process->id ?>.<?= $conn->id ?>"
							transform="rotate(<?= $conn->angle ?>,<?= $process->x ?>,<?= $process->y ?>)">
						<title><?= $conn->name ?></title>
					</circle>
					<?php } ?>
				</g>
				<?php } // foreach process?>

				<?php foreach ($model->terminals() as $term){ ?>
				<g>
					<?php if (!$term->type) { ?>
					<rect
							class="terminal"
							x="<?= $term->x - ($term->w/2)?>"
							y="<?= $term->y - 15 ?>"
							width="<?= $term->w ?>"
							height="30"
							id="terminal_<?= $term->id ?>">
						<title><?= $term->description ?></title>
					</rect>
					<?php } else { ?>
					<ellipse cx="<?= $term->x ?>" cy="<?= $term->y + 10 ?>" rx="<?= $term->w/2?>" ry="15" />
					<rect
							class="terminal"
							x="<?= $term->x - ($term->w/2)?>"
							y="<?= $term->y - 30 ?>"
							width="<?= $term->w ?>"
							height="40"
						  	stroke-dasharray="0,<?= $term->w ?>,40,<?= $term->w ?>,40"
							id="terminal_<?= $term->id ?>">
						<title><?= $term->description ?></title>
					</rect>
					<ellipse cx="<?= $term->x ?>" cy="<?= $term->y - 30 ?>" rx="<?= $term->w/2?>" ry="15" />//-->
					<?php } ?>
					<text x="<?= $term->x ?>" y="<?= $term->y ?>" fill="red"><?= $term->name ?></text>
				</g>
				<?php } // foreach process?>
			</svg>
		</td>
	</tr>
</table>
<?php
debug($model);
include '../common_templates/closure.php';