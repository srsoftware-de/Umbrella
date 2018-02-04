<?php $title = 'Umbrella Time Tracking';

include '../bootstrap.php';
include 'controller.php';
require_login('time');

$time_id = param('id');
assert(is_numeric($time_id),'No valid time id passed to drop!');

$time = end(load_times(['ids'=>$time_id]));


$confirm = $time['state'] == TIME_STATUS_STARTED ? 'yes' : null;

if ($confirm = param('confirm', $confirm)){
	if ($confirm == 'yes'){
		drop_time($time_id);		
	}
	redirect('../index');
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('This will remove the time "?"',$time['subject']) ?></legend>
	<?= t('Are you sure?')?>
	<a href="?confirm=yes" class="button"><?= t('Yes')?></a>
	<a href="view" class="button"><?= t('No')?></a>
</fieldset>

<?php include '../common_templates/closure.php';?>
