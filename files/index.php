<?php $title = 'Umbrella Files';

include '../bootstrap.php';
include 'controller.php';

require_login('files');
$path = param('path','user'.$user->id);
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
		<th><?= t('Actions') ?></th>
	</tr>
	<?php if (!in_array($parent,['.',''])){ ?>
	<tr>
		<td>
			<a href="?path=<?= $parent ?>">..</a>
		</td>
		<td></td>
	</tr>
	<?php } ?>
	<?php foreach ($entries['dirs'] as $dir){ 
	?>
	<tr>
		<td>
			<a href="?path=<?= $path.DS.$dir ?>">
				<span class="symbol"></span> <?= $dir ?>
			</a>
		</td>
		<td>
			<a class="symbol" title="delete" href="delete?file=<?= $path.DS.$dir ?>"></a>
		</td>
	</tr>
	<?php }?>
	<?php foreach ($entries['files'] as $file){ ?>
	<tr>
		<td>
			<a title="download" href="download?file=<?= $path.DS.$file ?>">
				<span class="symbol"></span> <?= $file ?>
			</a>
		</td>
		<td>
			<a class="symbol" title="share" href="share?file=<?= $path.DS.$file ?>"></a>
			<a class="symbol" title="delete" href="delete?file=<?= $path.DS.$file ?>"></a>
		</td>
	</tr>
	<?php }?>
</table>

<?php include '../common_templates/closure.php'; ?>
