<?php 

include '../bootstrap.php';
include 'controller.php';

require_login('task');

$options = [];
if ($ids = param('ids'))$options['ids'] = explode(',',$ids);
if ($project_ids = param('project_ids')) $options['project_ids'] = explode(',',$project_ids);
if ($ids_only = param('ids_only')) $options['ids_only'] = $ids_only;

die(json_encode(load_tasks($options)));
