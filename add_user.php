<?php

include 'controller.php';

require_login('company');

$company_id = param('id');

if (!$company_id) error('No company id passed to view!');

$company = reset(Company::load($company_id));

if ($new_user_id = param('new_user')){
	$company->add_user($new_user_id);
}


$title = $company->name.' - Umbrella';
$user_list = request('user','json');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

?>
<fieldset>
	<legend><?= t('current users')?></legend>
	<ul>
	<?php foreach ($company->users() as $uid) { ?>	
		<li><?= $user_list[$uid]['login']?></li>
	<?php } ?>
	</ul>
</fieldset>
<form method="POST">
	<fieldset><legend><?=  t('Add user to Project "?"',$company->name) ?></legend>
		<fieldset>
			<select name="new_user">
				<option value="" selected="selected">= <?= t('Select a user') ?> =</option>
				<?php foreach ($user_list as $id => $u){ if (in_array($id,$company->users())) continue; ?>
				<option value="<?= $id ?>"><?= $u['login']?></option>
				<?php }?>
			</select>
			<label>
			</label>	
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
