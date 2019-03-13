<?php include 'controller.php';

require_login('poll');

$polls = Poll::load();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Your polls')?></legend>
</fieldset>

<?php include '../common_templates/closure.php';