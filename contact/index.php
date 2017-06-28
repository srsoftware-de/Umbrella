<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login();
$contact_files = list_contact_files($user->id);
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<div class="contacts">
<?php foreach ($contact_files as $hash => $info){
	$file = request('files','download?file='.$hash,false,false); ?>
	<fieldset>
		<legend><?= basename($info['path'])?></legend>
		<span>
			<a href="<?= getUrl('files','download?file='.$hash) ?>">Download</a>
			<a href="<?= getUrl('files','add_user_to?file='.$hash) ?>">Share</a>
		</span>
		<?php debug ($file)?>
	</fieldset>
<?php } ?>
</div>

<?php include '../common_templates/closure.php'; ?>