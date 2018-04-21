<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$endpoint_path = param('id2');
assert(strpos($endpoint_path,':')!==false,'Parameter does not refer to process:connector');
$endpoint_path_parts = explode(':',$endpoint_path);
$connector_id = array_pop($endpoint_path_parts); // last part
$process_path = array_pop($endpoint_path_parts);
$process_hierarchy = explode('.',$process_path);

$model = Model::load(['ids'=>$model_id]);
$process = $model->processes(array_shift($process_hierarchy));
while(!empty($process_hierarchy)) $process = $process->children(array_shift($process_hierarchy));

$conn = $process->connectors($connector_id);
$conn->patch($_POST);
$conn->save();