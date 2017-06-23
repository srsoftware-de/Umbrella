<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login();

$task_id = param('id');
if (!$task_id) error('No task id passed!');
$task = load_task($task_id);
load_requirements($task);
$project_id = $task['project_id'];

if ($name = post('name')){
	$due_date = post('due_date');
	$modifier = post('extension');
	if ($due_date && $modifier) $due_date = date('Y-m-d',strtotime($due_date.' '.$modifier));		
	update_task($task_id,$name,post('description'),$project_id,post('parent_task_id'),post('start_date'),$due_date);
	update_task_requirements($task_id,post('required_tasks'));
	if ($target = param('redirect')){
		redirect($target);
	} else {
		redirect('view');
	}
}

$task['project'] = request('project','json?id='.$project_id);

$parent_task_id = $task['parent_task_id'];
if ($parent_task_id) $task['parent'] = load_task($task['parent_task_id']);

// load other tasks of the project for the dropdown menu
$project_tasks = get_task_list('name',$project_id);

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend>Edit <?= $task['name']?></legend>
		<fieldset>
			<legend>Project</legend>
			<?= $task['project']['name']?>
		</fieldset>
		<fieldset>
			<legend>Task</legend>
			<input type="text" name="name" value="<?= $task['name'] ?>" autofocus="true"/>
		</fieldset>
		<?php if ($project_tasks){?>
		<fieldset>
			<legend>Parent task</legend>
			<select name="parent_task_id">
			<option value="">= select parent task =</option>
			<?php foreach ($project_tasks as $id => $project_task) {?>
				<option value="<?= $id ?>" <?= ($id == $task['parent_task_id'])?'selected="selected"':''?>><?= $project_task['name']?></option>
			<?php }?>
			</select>
		</fieldset>
		
		<?php }?>
		<fieldset>
			<legend>Description</legend>
			<textarea name="description"><?= $task['description']?></textarea>
		</fieldset>
		<fieldset>
			<legend>Start date</legend>
			<input name="start_date" type="date" value="<?= $task['start_date'] ?>" />
		</fieldset>
		<fieldset>
			<legend>Due date</legend>
			<input name="due_date" type="date" value="<?= $task['due_date'] ?>" />
			<?php if ($task['due_date']) { ?>
			<select name="extension">
				<option value="">No extension</option>
				<option value="+1 week">+1 Week</option>
				<option value="+1 month">+1 Month</option>
				<option value="+1 year">+1 Year</option>
			</select>			
			<?php } ?>
		</fieldset>
		<?php if (!empty($project_tasks)) {?>
		<fieldset class="requirements">
			<legend>Requires completion of</legend>
			<?php foreach ($project_tasks as $id => $project_task){ ?>
			<label>
				<input type="checkbox" name="required_tasks[<?= $id?>]" <?= array_key_exists($id, $task['requirements'])?'checked="true"':'' ?>/>
				<?= $project_task['name']?>
			</label>
			<?php }?>
		</fieldset>
		<?php } ?>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
