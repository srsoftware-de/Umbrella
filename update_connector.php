<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$id2 = param('id2');
assert(strpos($id2,'.')!==false,'Parameter does not refer to process.connector');
$process_hierarchy = explode('.',$id2);
$conn_id = array_pop($process_hierarchy);

$model = Model::load(['ids'=>$model_id]);
$process = $model->processes(array_shift($process_hierarchy));
while(!empty($process_hierarchy)) $process = $process->children(array_shift($process_hierarchy));

$conn = $process->connectors($conn_id);
$conn->patch($_POST);
$conn->save();