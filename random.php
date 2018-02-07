<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login('task');

$task_id = getRandomTaskId();
redirect($task_id.'/view');
