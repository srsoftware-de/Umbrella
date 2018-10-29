<?php include 'controller.php';

require_login('company');

$companies = Company::load();
$projects = isset($services['project']) ? request('project','json',['company_ids'=>array_keys($companies)]) : null;
$user_list = request('user','json');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<div>

<?php foreach ($companies as $company){ ?>

<fieldset class="company">
	<legend>
		<span>
			<a href="<?= $company->id ?>/edit" class="symbol" title="<?= t('edit')?>"></a>
			<a href="<?= $company->id ?>/add_user" class="symbol" title="<?= t('add user') ?>"></a>
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
	<?php if ($projects) { ?>
	<fieldset>
		<legend><?= t('projects')?></legend>
		<ul>
		<?php foreach ($projects as $project) {
			if ($project['company_id'] != $company->id) continue; ?>	
			<li><a href="<?= getUrl('project',$project['id'].'/view')?>" ><?= $project['name']?></a></li>
		<?php } ?>
		</ul>
	</fieldset>
	<?php } ?>
	<fieldset>
		<legend><?= t('current users')?></legend>
		<ul>
		<?php foreach ($company->users() as $uid) { ?>	
			<li>
				<?= $user_list[$uid]['login']?> <a class="symbol" href="<?= getUrl('company',$company->id.DS.'drop_user?user='.$uid) ?>" title="<?= t('Drop user') ?>"></a>
			</li>
		<?php } ?>
		</ul>
	</fieldset>
	<?php if (isset($services['stock'])) { ?>
	<fieldset>
		<legend><?= t('Stock management') ?></legend>
		<a class="button" href="<?= getUrl('stock','company:'.$company->id.DS.'index' )?>"><?= t('go to stock management') ?></a>
	</fieldset>
	<?php } ?>
</fieldset>
<?php } ?>
</div>
<?php include '../common_templates/closure.php';