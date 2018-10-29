<?php include 'controller.php';

require_login('stock');

$companies = isset($services['company']) ? request('company','json') : null;
$prefix = param('id');
if ($prefix) {
	$parts = explode(':', $prefix,2);
	$realm = array_shift($parts);
	$realm_id = array_shift($parts);
	switch ($realm){
		case 'company':
			$owner = $companies[$realm_id]['name'];
			break;
		default:
			$prefix = null;
	}
}
if (!$prefix){
	$prefix = 'user:'.$user->id;
	$owner = $user->login;
}

$items = Item::load(['prefix'=>$prefix.':']);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

if ($companies){ ?>
<fieldset>
	<legend><?= t('Companies')?></legend>
	<?= t('You are viewing the items of <b>?</b>.',$owner)?><br/>
	<?= t('To view the stock of one of your companies, click on its name:')?>
	<?php 
	foreach ($companies as $company){ ?>
	<a class="button" href="<?= $base_url.'company:'.$company['id'].DS.'index'?>"><?= $company['name'] ?></a>
	<?php } // foreach company	?>
</fieldset>
<?php } // if companies ?>

<table class="stock">
	<tr>
		<th><?= t('ID')?></th>
		<th><?= t('Code')?></th>
		<th><?= t('Name')?></th>
		<th><?= t('Location')?></th>
	</tr>
	<?php foreach ($items as $item){ $url = $base_url.$item->id.DS.'view' ?>
	<tr>
		<td><a href="<?= $url ?>"><?= substr($item->id,strlen($prefix)+1) ?></a></td>
		<td><a href="<?= $url ?>"><?= $item->code ?></a></td>
		<td><a href="<?= $url ?>"><?= $item->name ?></a></td>
		<td><a href="<?= getUrl('stock',$item->id.'/alter_location'.($company_id?'?company='.$company_id:'')) ?>"><?= $item->location()->full() ?></a></td>
	</tr>
	<?php } // foreach item?>
</table>
<?php include '../common_templates/closure.php'; ?>
