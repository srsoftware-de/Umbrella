<?php

include 'controller.php';

require_login('company');

$company_id = param('id');
$user_id = param('user');

if (!$company_id) error('No company id passed to view!');
if (!$user_id) error('No user id passed to view!');

$company = reset(Company::load($company_id));

if (param('confirm') == 'yes'){
	$company->drop_user($user_id);
	redirect(getUrl('company'));
}

$title = $company->name.' - Umbrella';
$user = request('user','json',['ids'=>$user_id],false,OBJECT_CONVERSION);


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

?>

<fieldset>
	<legend><?= t('confirmation required') ?></legend>
	<?= t('Are you sure you want to remove <b>?</b> from ??',[$user->login,$company->name]) ?><br/>
	<a class="button" href="<?= location('id').'&confirm=yes'?>"><?= t('Yes')?></a>
	<a class="button" href="<?= getUrl('company') ?>"><?= t('No')?></a>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
