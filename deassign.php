<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_user_login();

$id = param('id');

if ($id === null) redirect('index');
deassign_service($id);