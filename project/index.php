<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login();
$projects = get_project_list(param('order'));
$show_closed = param('closed') == 'show';
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<?php if (!$show_closed){ ?>
<a href="?closed=show">show closed projects</a>
<?php }?>
<table>
	<tr>
		<th><a href="?order=name">Name</a></th>
		<th><a href="?order=status">Status</a></th>
		<th>Actions</th>
	</tr>
<?php foreach ($projects as $id => $project){
	if (!$show_closed && $project['status']>50) continue; 
	?>
	<tr>
		<td><a href="<?= $id ?>/view"><?= $project['name'] ?></a></td>
		<td><?= $PROJECT_STATES[$project['status']] ?></td>
		<td>
			<a title="edit"     href="<?= $id ?>/edit?redirect=../index"     class="symbol"></a>
			<a title="complete" href="<?= $id ?>/complete?redirect=../index" class="<?= $project['status'] == PROJECT_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
			<a title="cancel"   href="<?= $id ?>/cancel?redirect=../index"   class="<?= $project['status'] == PROJECT_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
			<a title="open"     href="<?= $id ?>/open?redirect=../index"     class="<?= $project['status'] == PROJECT_STATUS_OPEN     ? 'hidden':'symbol'?>"></a>
		</td>
	</tr>
<?php } ?>

</table>
<?php 
include '../common_templates/closure.php'; ?>
