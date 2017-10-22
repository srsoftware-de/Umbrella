<?php $title = 'Umbrella Files';

include '../bootstrap.php';
include 'controller.php';

require_login('files');
$folder = param('folder');
$files = list_files($user->id,$folder);

echo json_encode($files);
