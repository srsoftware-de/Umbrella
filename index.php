<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('company');

$companies = Company::load();
$projects = request('project','json',['company_ids'=>implode(',',array_keys($companies))]);
$user_list = request('user','list');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

foreach ($companies as $company){ ?>

<fieldset class="company">
	<legend>
		<span>
			<a href="<?= $company->id ?>/edit" class="symbol"></a>
			<a href="<?= $company->id ?>/add_user" class="symbol"></a>
		</span>
		<?= $company->name ?>
	</legend>
	<table class="vertical">
	<?php foreach (Company::fields() as $field => $props) {
		if (in_array($field,['id','name','currency','decimal_separator','decimals','thousands_separator'])) continue; 
		if (isset($company->{$field})){ ?>
		<tr>
			<th><?= t($field)?></th>
			<td><?= str_replace("\n","<br/>\n",$company->{$field}) ?></td>		
		</tr>	
	<?php }}?>
	</table>
	<fieldset>
		<legend><?= t('projects')?></legend>
		<ul>
		<?php foreach ($projects as $project) {
			if ($project['company_id'] != $company->id) continue; ?>	
			<li><a href="<?= getUrl('project',$project['id'].'/view')?>" ><?= $project['name']?></a></li>
		<?php } ?>
		</ul>
	</fieldset>
	<fieldset>
		<legend><?= t('current users')?></legend>
		<ul>
		<?php foreach ($company->users() as $uid) { ?>	
			<li><?= $user_list[$uid]['login']?></li>
		<?php } ?>
		</ul>
	</fieldset>
</fieldset>

<?php }

include '../common_templates/bottom.php';
