<?php $title = 'Umbrella Files';

include '../bootstrap.php';
include 'controller.php';

require_login('files');

$path = param('path');
if (in_array($path,['.','user'])) $path = null;
$entries = list_entries($path);
if (param('format') == 'json') die(json_encode($entries));

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Files: ?',$path?$path:' ')?></legend>
	<table>
		<tr>
			<th><?= t('File / Directory') ?></th>
			<th><?= t('Actions') ?></th>
		</tr>
		
		<?php foreach ($entries['dirs'] as $alias => $dir){ ?>
		<tr>
			<td>
				<a href="?path=<?= $dir ?>">
					<span class="symbol"></span> <?= $alias ?>
				</a>
			</td>
			<td>
				<a class="symbol" title="<?= t('rename') ?>" href="rename?file=<?= $dir ?>"></a>
				<a class="symbol" title="<?= t('delete')?>"  href="delete?file=<?= $dir ?>"></a>			
			</td>
		</tr>
		<?php }?>
		
		<?php foreach ($entries['files'] as $alias => $file){ 
			$filename = urlencode($file);	?>
		<tr>
			<td>
				<a title="download" href="download?file=<?= $filename ?>">
					<span class="symbol"></span> <?= $alias ?>
				</a>
			</td>
			<td>
				<a class="symbol" title=<?= t('share')?> href="share?file=<?= $filename ?>"></a>
				<a class="symbol" title="<?= t('rename') ?>" href="rename?file=<?= $filename ?>"></a>
				<a class="symbol" title="<?= t('delete')?>" href="delete?file=<?= $filename ?>"></a>
			</td>
		</tr>
		<?php }?>
		<tr>
			<td>
				<a href="shared">
					<span class="symbol"></span> <?= t('shared files')?>
				</a>
			</td>
			<td></td>
		</tr>
		
	</table>
</fieldset>
<?php include '../common_templates/closure.php'; ?>
