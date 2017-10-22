<?php $title = 'Umbrella File Management';

include '../bootstrap.php';
include 'controller.php';

require_login('files');
if (isset($_FILES['file'])){
	$file_info = $_FILES['file'];
	if ($file_info['size'] == 0) {
		error('Uploaded file is empty!');
	} else {
		$result = add_file($file_info,post('folder'),$user->id);
		if ($result === true) {
			redirect('index');
		} else error($result);
	}    	
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST" enctype="multipart/form-data">
	<fieldset><legend>Upload new file</legend>
		<fieldset>
			<legend>Folder</legend>
			<input type="text" <?= post('folder')?'value="'.post('folder').'"':'' ?> name="folder" />		
		</fieldset>
		<fieldset><legend>Name</legend>		
		<input type="file" name="file" />
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
