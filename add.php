<?php include 'controller.php';

require_login('files');

$dir = param('dir');
if (access_granted($dir) && !in_array($dir,['company','project'])){
	if (isset($_FILES['file'])){
		$file_info = $_FILES['file'];
		if ($file_info['size'] == 0) {
			error('Uploaded file is empty!');
		} else {
			$info = add_file($file_info);
			if (is_array($info)) {
				if (isset($services['bookmark'])){ // add to bookmarks
					$tags = explode(DS, $dir);
					array_splice($tags, array_search('user'.$user->id, $tags ), 1); // delete "userXX" from tags
					$tags[] = t('File');
					$tags[] = $info['name'];
					$display_url = getUrl('files','index?path='.$info['dir']);
					$tags=implode(' ', $tags);
				 	request('bookmark','add',['url'=>$display_url,'tags'=>$tags,'comment'=>$info['name']]);
				}
				redirect('index'.($dir?'?path='.$dir:''));
			} else error($info);
		}
	}
} else {
	error('You are not allowed to add files to "◊"!',$dir);
	redirect(getUrl('files','?path='.$dir));
}

$parts = explode(DS,$dir);
$realm = array_shift($parts);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST" enctype="multipart/form-data">
	<fieldset class="file">
		<legend><?= t('Upload new file'); ?></legend>
		<input type="file" name="file" />
		<?php if ($realm != 'user') { ?>
		<label>
			<input type="checkbox" checked="true" name="notify" />
			<?= t("Notifiy $realm users after upload")?>
		</label>
		<?php } ?>
		<button type="submit"><?= t('Save') ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
