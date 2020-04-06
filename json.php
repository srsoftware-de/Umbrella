<?php include 'controller.php';
require_login('task');

$options      = ['order' => param('order','due_date')];
if ($ids      = param('ids')) {
	$options['ids'] = $ids;
} elseif ($ids = param('id')){
	$options['ids'] = $ids;
}
if ($pids     = param('project_ids')) $options['project_ids'] = $pids;
if ($ids_only = param('ids_only'))    $options['ids_only']    = $ids_only;
if (param('load_closed') == true)     $options['load_closed'] = true;

die(json_encode(Task::load($options)));
