<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$id2 = param('id2');
assert(strpos($id2,'.')!==false,'Parameter does not refer to process.connector');
$id2 = explode('.',$id2);
$process_id = array_shift($id2);
$conn_id = array_shift($id2);

$model = Model::load(['ids'=>$model_id]);
$process = $model->processes($process_id);
$conn = $process->connectors($conn_id);
debug($conn);
$conn->patch($_POST);
debug($conn);
$conn->save();
debug($conn);