<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$process_id = param('id2');

if (!$model_id){
	error('No model id passed to terminal.');
	redirect(getUrl('model'));
}
if (!$process_id){
	error('No terminal id passed to terminal.');
	redirect(getUrl('model'));
}

$model = Model::load(['ids'=>$model_id]);
$process_hierarchy = explode('.',$process_id);
$process = $model->processes(array_shift($process_hierarchy));
while(!empty($process_hierarchy)) $process = $process->children(array_shift($process_hierarchy));

if ($child_process_id = param('process_id')){
	$process->addChild($child_process_id);
	redirect($model->url());
}

function list_proc($proc){ ?>
	<li>
		<label>
		<input type="radio" name="process_id" value="<?= $proc->id ?>" />
		<?= $proc->name ?>
		</label>
		<ul>
		<?php foreach ($proc->children() as $child) list_proc($child); ?>
		</ul>
	</li>
<?php }

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Add exsiting process to process "?"',$process->name)?>
		</legend>
		<ul>
		<?php foreach (Model::load(['project_id'=>$model->project_id]) as $other_model) { ?>
		<li>
			<?= $other_model->name ?>
			<ul>
			<?php foreach ($other_model->processes() as $proc) list_proc($proc); ?>
			</ul>
		</li>
		<?php }?>
		</ul>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';