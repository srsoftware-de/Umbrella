<?php $title = 'Umbrella Files';

include '../bootstrap.php';
include 'controller.php';

require_login('files');
$path = param('path','user'.$user->id);
$target = param('target');
if ($target === null) redirect('index');
$entries = list_entries($path);
$parent = dirname($path);
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<h1><?= t('Files: ?',$path?$path:' ')?></h1>
<table>
	<tr>
		<th><?= t('File / Directory') ?></th>
	</tr>
	<?php if (!in_array($parent,['.',''])){ ?>
	<tr>
		<td>
			<a title="<?= t('move one directory up') ?>" href="?target=<?= $target?>&path=<?= $parent ?>">
				<span class="symbol"></span> ..
			</a>
		</td>
	</tr>
	<?php } ?>
	<?php foreach ($entries['dirs'] as $dir){ 
	?>
	<tr>
		<td>
			<a href="?target=<?= $target?>&path=<?= $path.DS.$dir ?>">
				<span class="symbol"></span> <?= $dir ?>
			</a>
		</td>
	</tr>
	<?php }?>
	<?php foreach ($entries['files'] as $file){ ?>
	<tr>
		<td>
			<a title="select this file" href="<?= $target ?>?file=<?= $path.DS.$file ?>">
				<span class="symbol"></span> <?= $file ?>
			</a>
		</td>
	</tr>
	<?php }?>
</table>

<?php include '../common_templates/closure.php'; ?>
