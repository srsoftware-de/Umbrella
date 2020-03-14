<?php include 'controller.php';

require_login('files');

$path = param('path');
$entries = shared_files($path);
$context = in_array($path, ['project','user']) ? request($path,'json') : null;
$path = empty($path) ? '' : rtrim($path,DS).DS;

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<h1><?= t('shared files: ◊',$path?$path:' ')?></h1>
<?php  ?>
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
	<?php foreach ($entries as $file_name => $type){
		$name = isset($context[$file_name]['login']) ? $context[$file_name]['login'] : (isset($context[$file_name]['name']) ? $context[$file_name]['name'] : $file_name);
		?>
	<tr>
		<?php if ($type == 'file') {?>
		<td>
			<a title="<?= t('dowlnload file')?>" href="download?file=<?= $path.$file_name ?>">
				<span class="symbol"></span> <?= $name ?>
			</a>
		</td>
		<?php } else { ?>
		<td>
			<a title="<?= t('show folder')?>" href="?path=<?= $path.$file_name ?>">
				<span class="symbol"></span> <?= $name ?>
			</a>
		</td>
		<?php }?>
	</tr>
	<?php }?>
</table>

<?php include '../common_templates/closure.php'; ?>
