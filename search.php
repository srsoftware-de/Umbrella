<?php $title = 'Umbrella User Management';

include '../bootstrap.php';
include 'controller.php';

require_user_login();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

foreach ($services as $service => $data){
	if ($service == 'user') continue;	
	$result = request($service,'search',['key'=>param('search')],false,NO_CONVERSION);
	if ($result){ ?>
<fieldset>
	<legend><?= t($data['name'])?></legend>
	<?= $result ?>
</fieldset>
<?php }	
}

include '../common_templates/closure.php'; ?>
