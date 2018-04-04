<?php

include '../bootstrap.php';
include 'controller.php';

$title = t('Umbrella: Task Management');
require_login('task');

$task_id = param('id');
if (!$task_id) error('No task id passed!');
$task = load_tasks(['ids'=>$task_id]);

// get a map from user ids to permissions
$project_id = $task['project_id'];
$task['project'] = request('project','json',['ids'=>$project_id,'users'=>'true','single'=>true]);
$project_users = request('user','json',['ids'=>array_keys($task['project']['users'])]);
load_users($task,$project_users); // add users to task

load_requirements($task);

if ($name = post('name')){
	$task['name'] = $name;
	
	if ($start_date = post('start_date')){
		$modifier = post('start_extension');
		$task['start_date'] = $modifier ? date('Y-m-d',strtotime($start_date.' '.$modifier)) : $start_date;
	} else {
		$task['start_date'] = null;
	}
	
	if ($due_date = post('due_date')){
		$modifier = post('due_extension');
		$task['due_date'] = $modifier ? date('Y-m-d',strtotime($due_date.' '.$modifier)) : $due_date;
	} else {
		$task['due_date'] = null;
	}

	if ($description = post('description')) $task['description'] = $description;
	$parent = post('parent_task_id');
	if ($parent !== null) $task['parent_task_id'] = ($parent == 0) ? null : $parent;
	
	update_task($task);
	update_task_requirements($task['id'],post('required_tasks'));
	
	if ($target = param('redirect')){
		redirect($target);
	} else {
		redirect('view');
	}
}

if ($task['parent_task_id']) $task['parent'] = load_tasks(['ids'=>$task['parent_task_id']]);

// load other tasks of the project for the dropdown menu
$project_tasks = load_tasks(['order'=>'name','project_ids'=>$project_id]);

if (isset($services['bookmark'])){
	$hash = sha1(getUrl('task',$task_id.'/view'));
	$bookmark = request('bookmark','json_get?id='.$hash);
}

function show_project_task_checkbox($list, $id){
	global $task;
	$project_task = $list[$id];
?>
	<li>
		<label>
			<input type="checkbox" name="required_tasks[<?= $id?>]" <?= isset($task['requirements'][$id])?'checked="true"':'' ?>/>
			<?= $project_task['name']?>
		</label>
		<ul>
		<?php foreach ($list as $sub_id => $sub_task) {
			if (in_array($sub_task['status'],[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]))continue;
			if ($sub_task['parent_task_id'] == $id) show_project_task_checkbox($list,$sub_id);
		}
		?>
		</ul>
	</li>
	<?php	
}

function show_project_task_option($list, $id, $exclude_id, $space=''){
	global $task;
	if ($id == $exclude_id) return;
	$project_task = $list[$id];?>
	<option value="<?= $id ?>" <?= ($id == $task['parent_task_id'])?'selected="selected"':''?>><?= $space.$project_task['name']?></option>
	<?php foreach ($list as $sub_id => $sub_task) {
		if (in_array($sub_task['status'],[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]))continue;
		if ($sub_task['parent_task_id'] == $id) show_project_task_option($list,$sub_id,$exclude_id,$space.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
	}
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend><?= t('Edit "?"',$task['name']) ?></legend>
		<fieldset>
			<legend><?= t('Project')?></legend>
			<a href="<?= getUrl('project',$task['project']['id'].'/view')?>" ><?= $task['project']['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$task['project_id'] ?>" class="symbol" title="show project files" target="_blank"></a>
		</fieldset>
		<fieldset>
			<legend><?= t('Task')?></legend>
			<input type="text" name="name" value="<?= htmlspecialchars($task['name']) ?>" autofocus="true"/>
		</fieldset>
		<?php if ($project_tasks){?>
		<fieldset>
			<legend><?= t('Parent task')?></legend>
			<select name="parent_task_id">
			<option value=""><?= t('= select parent task =') ?></option>
			<?php foreach ($project_tasks as $id => $project_task) {
				if (in_array($project_task['status'],[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]))continue;
				if ($project_task['parent_task_id'] == null) show_project_task_option($project_tasks,$id,$task_id);
			} ?>
			</select>
		</fieldset>
		
		<?php }?>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="description"><?= $task['description']?></textarea>
		</fieldset>
		<fieldset>
			<legend><?= t('Estimated time')?></legend>
			<label>
				<?= t('? hours','<input type="number" name="est_time" value="'.htmlspecialchars($task['est_time']).'" />')?>				 
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
			<input name="start_date" type="date" value="<?= htmlspecialchars($task['start_date']) ?>" />
			<?php if ($task['start_date']) { ?>
			<select name="start_extension">
				<option value=""><?= t('No extension') ?></option>
				<option value="+1 week"><?= t('+one week')?></option>
				<option value="+1 month"><?= t('+one month')?></option>
				<option value="+3 months"><?= t('+three months')?></option>
				<option value="+6 months"><?= t('+six months')?></option>
				<option value="+1 year"><?= t('+one year')?></option>
				
			</select>			
			<?php } ?>
			</fieldset>
		<fieldset>
			<legend><?= t('Due date')?></legend>
			<input name="due_date" type="date" value="<?= htmlspecialchars($task['due_date']) ?>" />
			<?php if ($task['due_date']) { ?>
			<select name="due_extension">
				<option value=""><?= t('No extension') ?></option>
				<option value="+1 week"><?= t('+one week')?></option>
				<option value="+1 month"><?= t('+one month')?></option>
				<option value="+3 months"><?= t('+three months')?></option>
				<option value="+6 months"><?= t('+six months')?></option>
				<option value="+1 year"><?= t('+one year')?></option>
			</select>			
			<?php } ?>
		</fieldset>
		<?php if (!empty($project_tasks)) {?>
		<fieldset class="requirements">
			<legend><?= t('Requires completion of')?></legend>
			<ul>
			<?php foreach ($project_tasks as $id => $project_task){
				if (in_array($project_task['status'],[TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED]))continue;
				if ($project_task['parent_task_id'] == null) show_project_task_checkbox($project_tasks,$id); 
			}?>
			</ul>
		</fieldset>
		<?php } ?>
		<label class="silent_box">
			<input type="checkbox" name="silent" /> <?= t("Don't notify users") ?>
		</label>
		<input type="submit" />
	</fieldset>
</form>
<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'task:'.$task_id,'form'=>false],false,NO_CONVERSSION);

include '../common_templates/closure.php'; ?>
