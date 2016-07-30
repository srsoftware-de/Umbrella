<?php

include '../bootstrap.php';

$perms = request('permission','get?token='.$token);
if (!is_array($perms)) die('no permission');

if (!is_array($perms['project'])) die('no permission');
if (!in_array('add', $perms['project'])) die('no permission');

if (!isset($_GET['name'])) die('no project name given');
$name = $_GET['name'];
if ($name == "") die ('no project name given');
	
if (!isset($_GET['description'])) $_GET['description']='';
$description = $_GET['description'];

$project_list = json_decode(file_get_contents('.projects'),true);
if (!is_array($project_list)) $project_list=array();
if (array_key_exists($name, $project_list)) die('Project already exists');
$project_list[$name] = $description;

file_put_contents('.projects', json_encode($project_list));
print_r($project_list);
header('Location: ../..?token='.$token);
?>
