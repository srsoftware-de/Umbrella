<?php 

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
$project_id = param('id');

if (!$project_id) error('No project id passed to view!');

$p = load_project($project_id);
$title = $p['name'].' - Umbrella';

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<h1><?= $p['name'] ?></h1>
<table class="vertical">
	<tr>
		<th>Project</th><td><?= $p['name'];?></td>
	</tr>
	<tr>
		<th>Description</th><td><?= $p['description']; ?></td>
	</tr>	
</table>
<?php include '../common_templates/closure.php'; ?>
