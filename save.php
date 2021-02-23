<?php include 'controller.php';

require_login('mindmap');

$response = save();

debug($response);

