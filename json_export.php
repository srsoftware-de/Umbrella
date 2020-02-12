<?php include 'controller.php';

require_login('project');

$notes_available = isset($services['notes']);

if ($project_id = param('id')){
	$project = Project::load(['ids'=>$project_id,'users'=>true]);
	if ($project){
		if ($options = param('options')){
			unset($project->id);
			unset($project->users);
			unset($project->company_id);

			if (!empty($options['tasks'])){
				$project->tasks = [];
				$tasks = request('task','json',['order'=>'name','project_ids'=>$project_id]);
				foreach ($tasks as $tid => &$task){
					$parent_id = $task['parent_task_id'];
					unset($task['id']);
					unset($task['parent_task_id']);
					unset($task['project_id']);

					if ($notes_available && !empty($options['notes'])){
						$notes = request('notes','json',['uri'=>'task:'.$tid]);
						if (!empty($notes)){
							$task['notes'] = [];
							foreach ($notes as $nid => $note){
								unset($note['uri']);
								unset($note['id']);
								$task['notes'][$nid] = $note;
							}
						}
					}

					if (empty($parent_id)){
						$project->tasks[$tid] = &$task;
					} else {
						$parent_task = &$tasks[$parent_id];
						if (!isset($parent_task['children'])) $parent_task['children'] = [];
						$parent_task['children'][$tid] = &$task;
					}
				}
			}

			if ($notes_available && !empty($options['notes'])){
				$notes = request('notes','json',['uri'=>'project:'.$project_id]);
				if (!empty($notes)){
					$project->notes = [];
					foreach ($notes as $nid => $note){
						unset($note['uri']);
						unset($note['id']);
						$project->notes[$nid] = $note;
					}
				}
			}

			header('Content-Disposition: attachment; filename="'.$project->name.'.json"');
			print json_encode($project,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK);
			die();
		}
	} else error('You are not member of this project!');
	unset($project->id);
} else error('No project id passed to view!');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend><?= t('export project')?></legend>
	<p><label><input type="checkbox" name="options[tasks]" checked="checked"/>&nbsp;<?= t('Export tasks')?></label></p>
	<?php if ($notes_available) { ?>
	<p><label><input type="checkbox" name="options[notes]" />&nbsp;<?= t('Export notes')?></label></p>
	<?php } ?>
	<button type="submit"><?= t('start export')?></button>
</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
