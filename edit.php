<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';

require_login('task');

$task_id = param('id');
if (!$task_id) error('No task id passed!');
$task = load_tasks(['ids'=>$task_id]);

// get a map from user ids to permissions
$project_user_permissions = request('project','user_list',['id'=>$task['project_id']]);
$project_user_ids = array_keys($project_user_permissions);
$project_users = request('user','list',['ids'=>implode(',', $project_user_ids)]);
load_users($task,$project_users); // add users to task

load_requirements($task);
$project_id = $task['project_id'];

if ($name = post('name')){
	$task['name'] = $name;
	
	if ($start_date = post('start_date')){
		$modifier = post('start_extension');
		$task['start_date'] = $modifier ? date('Y-m-d',strtotime($start_date.' '.$modifier)) : $start_date;
	}
	
	if ($due_date = post('due_date')){
		$modifier = post('due_extension');
		$task['due_date'] = $modifier ? date('Y-m-d',strtotime($due_date.' '.$modifier)) : $due_date;
	}

	if ($description = post('description')) $task['description'] = $description;
	if ($parent = post('parent_task_id')) $task['parent_task_id'] = $parent;
	
	update_task($task);
	update_task_requirements($task['id'],post('required_tasks'));
	
	if ($target = param('redirect')){
		redirect($target);
	} else {
		redirect('view');
	}
}

$task['project'] = request('project','json',['ids'=>$project_id,'single'=>true]);

if ($task['parent_task_id']) $task['parent'] = load_tasks(['ids'=>$task['parent_task_id']]);

// load other tasks of the project for the dropdown menu
$project_tasks = load_tasks(['order'=>'name','project_ids'=>$project_id]);

if (isset($services['bookmark'])){
	$hash = sha1(getUrl('task',$task_id.'/view'));
	$bookmark = request('bookmark','json_get?id='.$hash);
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend><?= t('Edit "?"',$task['name']) ?></legend>
		<fieldset>
			<legend><?= t('Project')?></legend>
			<a href="<?= getUrl('project',$task['project']['id'].'/view')?>" ><?= $task['project']['name']?></a>
		</fieldset>
		<fieldset>
			<legend><?= t('Task')?></legend>
			<input type="text" name="name" value="<?= $task['name'] ?>" autofocus="true"/>
		</fieldset>
		<?php if ($project_tasks){?>
		<fieldset>
			<legend><?= t('Parent task')?></legend>
			<select name="parent_task_id">
			<option value="">= select parent task =</option>
			<?php foreach ($project_tasks as $id => $project_task) {?>
				<option value="<?= $id ?>" <?= ($id == $task['parent_task_id'])?'selected="selected"':''?>><?= $project_task['name']?></option>
			<?php }?>
			</select>
		</fieldset>
		
		<?php }?>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">click here for Markdown and extended Markdown cheat sheet</a>','https://www.markdownguide.org/cheat-sheet')?></legend>
			<textarea name="description"><?= $task['description']?></textarea>
		</fieldset>
		<fieldset>
			<legend><?= t('Estimated time')?></legend>
			<label>
				<?= t('? hours','<input type="number" name="est_time" value="'.$task['est_time'].'" />')?>				 
			</label>
		</fieldset>
		<?php if (isset($services['bookmark'])){ ?>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input type="text" name="tags" value="<?= $bookmark ? implode(' ', $bookmark['tags']) : ''?>" />
		</fieldset>
		<?php } ?>
		<fieldset>
			<legend><?= t('Start date')?></legend>
			<input name="start_date" type="date" value="<?= $task['start_date'] ?>" />
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
			<input name="due_date" type="date" value="<?= $task['due_date'] ?>" />
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
			<?php foreach ($project_tasks as $id => $project_task){
				if ($id == $task_id) continue; 
			?>
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
<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'task:'.$task_id],false,NO_CONVERSSION);

include '../common_templates/closure.php'; ?>
