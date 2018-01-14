<?php $title = 'Umbrella Files';

include '../bootstrap.php';
include 'controller.php';

require_login('files');

$path = param('path');
$shared_files = shared_files();
if ($path){
	$parent = dirname($path);
	$parts = explode(DS, $path);
	while ($part = array_shift($parts)) $shared_files = $shared_files[$part];
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<h1><?= t('shared files: ?',$path?$path:' ')?></h1>
<?php if ($path) $path = rtrim($path,DS).DS; ?>
<table>
	<tr>
		<th><?= t('File / Directory') ?></th>
	</tr>
	<?php if ($path) {
		$dir = dirname($path);
		$up = ($dir == '.')? 'shared': '?path='.$dir;
	?>
	<tr>
		<td><a title="<?= t('move one level up') ?>" href="<?= $up ?>">..</a></td>
	</tr>
	<?php }?>
	<?php foreach ($shared_files as $entry => $content){ ?>
	<tr>
		<?php if ($content == $path.$entry) {?>
		<td>
			<a title="<?= t('dowlnload file')?>" href="download?file=<?= $path.$entry ?>">
				<span class="symbol"></span> <?= $entry ?>
			</a>
		</td>
		<?php } else { ?>
		<td>
			<a title="<?= t('show folder')?>" href="?path=<?= $path.$entry ?>">
				<span class="symbol"></span> <?= $entry ?>
			</a>
		</td>
		<?php }?>
	</tr>
	<?php }?>
</table>

<?php include '../common_templates/closure.php'; ?>
