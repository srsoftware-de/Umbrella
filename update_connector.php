<?php include 'controller.php';

require_login('model');

$model_id = param('id1');
$connector_id = param('id2');

$model = Model::load(['ids'=>$model_id]);
$conn = $model->connector_instances($connector_id);
$conn->patch($_POST);
$conn->save();