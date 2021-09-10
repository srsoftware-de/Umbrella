<?php include 'controller.php';
require_login('task');

$task_id = param('id');
if (empty($task_id)){
	error('No task id passed!');
	redirect(getUrl('task'));
}

$task = Task::load(['ids'=>$task_id]);
if (empty($task)){
	error('You don`t have access to that task!');
	redirect(getUrl('task'));
}

if (!$task->is_writable()){
	error('You are not allowed to modify this task.');
	redirect(getUrl('task',$task->id.'/view'));
}

$projects = request('project','json',['users'=>'true']);

// load other tasks of the project for the dropdown menu
$project_tasks = Task::load(['order'=>'name','project_ids'=>$task->project_id]);

if ($name = post('name')){
	try {
		$task->patch(['name'=>$name]);

		if ($start_date = post('start_date')){
			$modifier = post('start_extension');
			$modifier = empty($modifier) ? '' : ' '.$modifier;
			$stamp = strtotime($start_date.$modifier);
			if ($stamp === false) throw_exception('Start date (◊) is not a valid date!',$start_date.$modifier);
			$task->patch(['start_date' => date('Y-m-d',$stamp)]);
		} else $task->patch(['start_date' => null]);

		if ($due_date = post('due_date')){
			$modifier = post('due_extension');
			$modifier = empty($modifier) ? '' : ' '.$modifier;
			$stamp = strtotime($due_date.$modifier);
			if ($stamp === false) throw_exception('Due date (◊) is not a valid date!',$due_date.$modifier);
			$task->patch(['due_date' => date('Y-m-d',$stamp)]);
		} else $task->patch(['due_date' => null]);

		$description = post('description');
		if ($description !== null) $task->patch(['description' => empty($description)?'':$description]);

		$est_time = post('est_time');
		if ($est_time !== null) $task->patch(['est_time' => empty($est_time)?null:$est_time]);

		$parent = post('parent_task_id');
		if (!empty($parent)) {
			if (!array_key_exists($parent, $project_tasks)) throw new Exception('Parent task must belong to the same project as the edited task!');
			$task->patch(['parent_task_id' => $parent]);
		} else $task->patch(['parent_task_id' => null]);

		if ($new_project_id = post('project_id')) {
			if (!array_key_exists($new_project_id, $projects)) throw new Exception('Task must reference existing project!');
			if ($new_project_id != $task->project_id) $task->patch(['project_id' => $new_project_id,'parent_task_id' => null]);
		}
		$task->patch(['show_closed'=>(post('show_closed','off')=='on')?1:0]);
		$task->patch(['no_index'=>(post('no_index','off')=='on')?1:0]);
		$task->save()->update_requirements(post('required_tasks'));
		redirect(param('redirect',getUrl('task',$task->id.'/view')));
	} catch (Exception $e){
		error($e->getMessage());
	}
}


if (isset($services['bookmark'])){
	$hash = sha1(getUrl('task',$task_id.'/view'));
	$bookmark = request('bookmark',$hash.'/json');
}

function show_project_task_checkbox($list, $id){
	global $task;
	$project_task = $list[$id];
?>
	<li>
		<label>
			<input type="checkbox" name="required_tasks[<?= $id?>]" <?= !empty($task->requirements($id))?'checked="true"':'' ?>/>
			<?= $project_task->name ?>
		</label>
		<ul>
		<?php foreach ($list as $sub_id => $sub_task) {
			if (in_array($sub_task->status,[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]))continue;
			if ($sub_task->parent_task_id == $id) show_project_task_checkbox($list,$sub_id);
		}
		?>
		</ul>
	</li>
	<?php
}

function show_project_task_option($list, $id, $exclude_id, $space=''){
	global $task;
	if ($id == $exclude_id) return;
	$project_task = $list[$id];
	$state = in_array($project_task->status,[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]) ? '['.t(task_state($project_task->status)).'] ' : "" ?>
	<option value="<?= $id ?>" <?= ($id == $task->parent_task_id)?'selected="selected"':''?>><?= $space.$state.$project_task->name ?></option>
	<?php foreach ($list as $sub_id => $sub_task) { // show open subtasks
		if (in_array($sub_task->status,[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]))continue;
		if ($sub_task->parent_task_id == $id) show_project_task_option($list,$sub_id,$exclude_id,$space.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
	}
	foreach ($list as $sub_id => $sub_task) { // show closed subtasks
		if (!in_array($sub_task->status,[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]))continue;
		if ($sub_task->parent_task_id == $id) show_project_task_option($list,$sub_id,$exclude_id,$space.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
	}
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend><?= t('Edit "◊"',$task->name) ?></legend>
		<fieldset>
			<legend><?= t('Project')?></legend>
			<select name="project_id">
			<?php foreach ($projects as $pid => $project){ if ($project['status'] >= PROJECT_STATUS_COMPLETE && $project['id']!=$task->project_id) continue; ?>
				<option value="<?= $pid ?>" <?= ($pid == $task->project_id)?'selected="selected"':''?>><?= $project['name'] ?></option>
			<?php }?>
			</select>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$task->project_id ?>" class="symbol" title="show project files" target="_blank"></a>
		</fieldset>
		<fieldset>
			<legend><?= t('Task')?></legend>
			<input type="text" name="name" value="<?= htmlspecialchars($task->name) ?>" autofocus="autofocus"/>
		</fieldset>
		<?php if (!empty($project_tasks)){?>
		<fieldset>
			<legend><?= t('Parent task')?></legend>
			<select name="parent_task_id">
			<option value=""><?= t('= select parent task =') ?></option>
			<?php foreach ($project_tasks as $id => $project_task) { // show open tasks
				if (in_array($project_task->status,[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]))continue;
				if ($project_task->parent_task_id == null) show_project_task_option($project_tasks,$id,$task->id);
			}
			foreach ($project_tasks as $id => $project_task) { // show closed tasks
				if (!in_array($project_task->status,[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]))continue;
				if ($project_task->parent_task_id == null) show_project_task_option($project_tasks,$id,$task->id);
			}
			?>
			</select>
		</fieldset>

		<?php }?>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="◊">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea id="preview-source" name="description"><?= htmlspecialchars($task->description) ?></textarea>
			<div id="preview"><?= markdown($task->description) ?></div>
		</fieldset>
		<fieldset>
			<legend><?= t('Estimated time')?></legend>
			<label>
				<?= t('◊ hours','<input type="number" name="est_time" value="'.htmlspecialchars($task->est_time).'" />')?>
			</label>
		</fieldset>
		<?php if (isset($services['bookmark'])){ ?>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input type="text" name="tags" value="<?= $bookmark ? htmlspecialchars(implode(' ', $bookmark['tags'])) : ''?>" />
		</fieldset>
		<?php } ?>
		<fieldset>
			<legend><?= t('Start date')?></legend>
			<input name="start_date" type="date" value="<?= htmlspecialchars($task->start_date) ?>" />
			<?php if ($task->start_date) { ?>
			<select name="start_extension">
				<option value=""><?= t('No extension') ?></option>
				<option value="+1 week"><?= t('+one week')?></option>
				<option value="+2 weeks"><?= t('+two weeks')?></option>
				<option value="+1 month"><?= t('+one month')?></option>
				<option value="+2 month"><?= t('+two months')?></option>
				<option value="+3 months"><?= t('+three months')?></option>
				<option value="+6 months"><?= t('+six months')?></option>
				<option value="+1 year"><?= t('+one year')?></option>
			</select>
			<?php } ?>
			</fieldset>
		<fieldset>
			<legend><?= t('Due date')?></legend>
			<input name="due_date" type="date" value="<?= htmlspecialchars($task->due_date) ?>" />
			<?php if ($task->due_date) { ?>
			<select name="due_extension">
				<option value=""><?= t('No extension') ?></option>
				<option value="+1 week"><?= t('+one week')?></option>
				<option value="+2 weeks"><?= t('+two weeks')?></option>
				<option value="+1 month"><?= t('+one month')?></option>
				<option value="+2 month"><?= t('+two months')?></option>
				<option value="+3 months"><?= t('+three months')?></option>
				<option value="+6 months"><?= t('+six months')?></option>
				<option value="+1 year"><?= t('+one year')?></option>
			</select>
			<?php } ?>
		</fieldset>
		<?php if (!empty($project_tasks)) { ?>
		<fieldset class="requirements">
			<legend><?= t('Requires completion of')?></legend>
			<ul>
			<?php foreach ($project_tasks as $id => $project_task){
				if (in_array($project_task->status,[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]))continue;
				if ($project_task->parent_task_id == null) show_project_task_checkbox($project_tasks,$id);
			}?>
			</ul>
		</fieldset>
		<?php }?>
		<fieldset class="options">
			<legend><?= t('Options')?></legend>
			<label>
				<input type="checkbox" name="show_closed" <?= $task->show_closed == 1 ? 'checked="checked"':''?>/> <?= t("Always show closed sub-tasks") ?>
			</label>
			<label>
				<input type="checkbox" name="no_index" <?= $task->no_index == 1 ? 'checked="checked"':''?>/> <?= t("Do not show on index page") ?>
			</label>
			<label class="silent_box">
				<input type="checkbox" name="silent" /> <?= t("Don't notify users") ?>
			</label>
		</fieldset>
		<button type="submit">
			<?= t('Save task') ?>
		</button>
	</fieldset>
</form>
<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'task:'.$task->id,'form'=>false],false,NO_CONVERSION);

include '../common_templates/closure.php'; ?>
