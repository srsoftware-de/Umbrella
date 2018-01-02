<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login('task');

$task_id = param('id');
if (!$task_id) error('No task id passed!');
$task = get_tasks(['id'=>$task_id]);
load_requirements($task);
$project_id = $task['project_id'];

if ($name = post('name')){
	$due_date = post('due_date');
	$modifier = post('due_extension');
	if ($due_date && $modifier) $due_date = date('Y-m-d',strtotime($due_date.' '.$modifier));

	$start_date = post('start_date');
	$modifier = post('start_extension');
	if ($start_date && $modifier) $start_date = date('Y-m-d',strtotime($start_date.' '.$modifier));
	
	update_task($task_id,$name,post('description'),$project_id,post('parent_task_id'),$start_date,$due_date);
	update_task_requirements($task_id,post('required_tasks'));
	
	if (isset($services['bookmark']) && ($raw_tags = param('tags'))){
		$raw_tags = explode(' ', str_replace(',',' ',$raw_tags));
		$tags = [];
		foreach ($raw_tags as $tag){
			if (trim($tag) != '') $tags[]=$tag;
		}
		request('bookmark','add',['url'=>getUrl('task').$task_id.'/view','comment'=>$name,'tags'=>$tags]);
	}
	
	if ($target = param('redirect')){
		redirect($target);
	} else {
		redirect('view');
	}
}

$task['project'] = request('project','json',['ids'=>$project_id,'single'=>ture]);

if ($task['parent_task_id']) $task['parent'] = get_tasks(['id'=>$task['parent_task_id']]);

// load other tasks of the project for the dropdown menu
$project_tasks = get_tasks(['order'=>'name','project_id'=>$project_id]);

if (isset($services['bookmark'])){
	$hash = sha1(getUrl('task',$task_id.'/view'));
	$bookmark = request('bookmark','json_get?id='.$hash);
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend>Edit "<?= $task['name']?>"</legend>
		<fieldset>
			<legend>Project</legend>
			<a href="<?= getUrl('project',$task['project']['id'].'/view')?>" ><?= $task['project']['name']?></a>
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
		<?php if (isset($services['bookmark'])){ ?>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input type="text" name="tags" value="<?= $bookmark ? implode(' ', $bookmark['tags']) : ''?>" />
		</fieldset>
		<?php } ?>
		<fieldset>
			<legend>Start date</legend>
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
			<legend>Due date</legend>
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
			<legend>Requires completion of</legend>
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

<?php include '../common_templates/closure.php'; ?>
