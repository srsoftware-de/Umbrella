<?php $title = 'Umbrella Files';

include '../bootstrap.php';
include 'controller.php';

require_login('files');

$path = param('path');
$shared_files = shared_files();
$parent = dirname($path);

if ($path){
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
		<th><?= t('Actions') ?></th>
	</tr>
	<?php if ($path) {
		$dir = dirname($path);
		$up = ($dir == '.')? 'shared': '?path='.$dir;
	?>
	<tr>
		<td><a href="<?= $up ?>">..</a></td>
		<td></td>
	</tr>
	<?php }?>
	<?php foreach ($shared_files as $entry => $content){ ?>	
	<tr>
		<?php if ($content == 'file') {?>
		<td>
			<a href="download?file=<?= $path.$entry ?>">
				<span class="symbol"></span> <?= $entry ?>
			</a>
		</td>
		<td></td>
		<?php } else { ?>
		<td>
			<a href="?path=<?= $path.$entry ?>">
				<span class="symbol"></span> <?= $entry ?>
			</a>
		</td>
		<td></td>
		<?php }?>
	</tr>		
	<?php }?>
</table>

<?php include '../common_templates/closure.php'; ?>
