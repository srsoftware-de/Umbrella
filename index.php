<?php include 'controller.php';

require_login('files');

$path = param('path');
if (in_array($path,['.','user'])) $path = null;
$entries = list_entries($path);
if (param('format') == 'json') die(json_encode($entries));

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<script type="text/javascript">
	function copyMarkdown(elem){
		var textareas = elem.getElementsByTagName('textarea');
		if (textareas.length>0) {
			textareas[0].select();
			document.execCommand("copy");
			alert('<?= t('Copied markdown to clipboard.') ?>');
		}
		return false;
	}
</script>

<fieldset>
	<legend><?= t('Files: ◊',$path?$path:' ')?></legend>
	<table>
		<tr>
			<th><?= t('File / Directory') ?></th>
			<th><?= t('Actions') ?></th>
		</tr>
		<?php foreach ($entries['dirs'] as $alias => $dir){ ?>
		<tr>
			<td>
				<a href="?path=<?= $dir ?>">
					<span class="symbol"></span> <?= $alias ?>
				</a>
			</td>
			<td>
				<?php if (!in_array($dir,['project','company','user/'.$user->id])) {?>
				<a class="symbol" title="<?= t('rename') ?>" href="rename?file=<?= $dir ?>"></a>
				<a class="symbol" title="<?= t('delete')?>"  href="delete?file=<?= $dir ?>"></a>
				<?php }?>
			</td>
		</tr>
		<?php }?>

		<?php foreach ($entries['files'] as $alias => $file){
			$filename = urlencode($file);	?>
		<tr>
			<td>
				<a title="download" href="download?file=<?= $filename ?>">
					<span class="symbol"></span> <?= $alias ?>
				</a>
			</td>
			<td>
				<a class="symbol" title="<?= t('share')?>" href="share?file=<?= $filename ?>"></a>
				<a class="symbol" title="<?= t('rename') ?>" href="rename?file=<?= $filename ?>"></a>
				<a class="symbol" title="<?= t('delete')?>" href="delete?file=<?= $filename ?>"></a>
				<?php if (is_image($alias)) { ?>
				<a class="symbol" title="<?= t('Copy markdown to clipboard') ?>" href="#" onclick="return copyMarkdown(this);"> <textarea class="copytext">![<?= t('alt text')?>](<?= getUrl('files','download?file='.$filename)?>)</textarea></a>
				<?php } ?>
			</td>
		</tr>
		<?php }?>
		<tr>
			<td>
				<a href="shared">
					<span class="symbol"></span> <?= t('shared files')?>
				</a>
			</td>
			<td></td>
		</tr>
	</table>
</fieldset>
<?php if (isset($services['bookmark'])) echo request('bookmark','html',['hash'=>sha1(getUrl('files','index?path='.$path))],false,NO_CONVERSION); ?>
<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'files:'.$path],false,NO_CONVERSION); ?>
<?php include '../common_templates/closure.php'; ?>
