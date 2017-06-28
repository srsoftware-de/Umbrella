<?php $title = 'Umbrella Files';

include '../bootstrap.php';
include 'controller.php';

require_login();
$files = list_files($user->id);
load_users($files);
//debug($files);
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<h1>Files</h1>
<table>
	<tr>
		<th>Directory</th>
		<th>Name</th>
		<th>Users</th>
		<th>Actions</th>
	</tr>
	<?php foreach ($files as $hash => $file){ ?>
	<tr>
		<td><?= dirname($file['path'])?></td>
		<td><?= basename($file['path'])?></td>
		<td>
			<?php foreach ($file['users'] as $uid => $user){ ?>
			<?= $user['login']?> (<?= $FILE_PERMISSIONS[$user['permissions']]?>)<br/>
			<?php }?>
		</td>
		<td>
			<a href="add_user_to?file=<?= $hash ?>">add user</a>
			<a href="delete?file=<?= $hash ?>">delete</a>
			<a href="download?file=<?= $hash ?>">download</a>
		</td>
	</tr>
	<?php }?>
</table>

<?php include '../common_templates/closure.php'; ?>