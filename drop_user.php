<?php

include 'controller.php';

require_login('company');

$company_id = param('id');
$user_id = param('user');

if (!$company_id){
	error('No company id passed to view!');
	redirect(getUrl('company'));
}
if (!$user_id) {
	error('No user id passed to view!');
	redirect(getUrl('company'));
}

$company = Company::load(['ids'=>$company_id]);

if (count($company->users())<2){
	warn('You may not remove the last user of a company!');
	redirect(getUrl('company',$company_id.DS.'view'));
}

if (param('confirm') == 'yes'){
	$company->drop_user($user_id);
	redirect(getUrl('company',$company_id.DS.'view'));
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
