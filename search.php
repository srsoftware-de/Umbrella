<?php
include '../bootstrap.php';
include 'controller.php';

require_user_login();

if ($key = param('key')){
	$title = 'Umbrella User Management';
} else {
	error('You need to specify a search key!');
	redirect('index');
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<h2><?= t('Your search provided the following results:') ?></h2>

<?php foreach ($services as $service => $data){
	if ($service == 'user') continue;
	$result = request($service,'search',['key'=>$key],false,NO_CONVERSION);
	if ($result){ ?>
<fieldset class="<?= $service ?>">
	<legend><?= t($data['name'])?></legend>
	<?= $result ?>
</fieldset>
<?php }
}

include '../common_templates/closure.php'; ?>
