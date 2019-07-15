<?php include 'controller.php';

require_login('files');

$target = param('target');

if ($target === null) redirect('index');
$path = param('path');
if (in_array($path,['.','user'])) $path = null;
$entries = list_entries($path);
$parent = dirname($path);

$message=param('message',t('Select a folder:'));
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<h2><?= $message ?></h2>
<?php if ($path) { ?><h3><?= t('current folder: ◊',$path) ?></h3><?php } ?>

<table>
	<tr>
		<th><?= t('File / Directory') ?></th>
		<th><?= t('Action') ?></th>
	</tr>
	<?php foreach ($entries['dirs'] as $alias => $dir){
	?>
	<tr>
		<td>
			<a href="?target=<?= $target.'&message='.urlencode($message) ?>&path=<?= $dir ?>">
				<span class="symbol"></span> <?= $alias ?>
			</a>
		</td>
		<td>
			<?php if ($alias != '..') { ?>
			<a href="<?= $target ?>?path=<?= urlencode($dir)?>"><?= t('select') ?></a>
			<?php }?>
		</td>

	</tr>
	<?php }?>
</table>

<?php include '../common_templates/closure.php'; ?>
