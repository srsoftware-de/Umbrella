<?php include 'controller.php';
require_login('task');

redirect(Task::random_id().'/view');
