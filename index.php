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
			if (array_key_exists($realm_id, $companies)){
				$owner = $companies[$realm_id]['name'];				
				break;
			} else warn('You are not allowed to access the stock of this company!');
		default:
			$prefix = null;
	}
}
if (!$prefix){
	$prefix = 'user:'.$user->id;
	$owner = $user->login;
}

$options = ['prefix'=>$prefix.':'];
if ($order = param('order')) $options['order']=$order;
$items = Item::load($options);

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

<fieldset>
	<legend><?= t('Overview')?></legend>
	<table class="stock">
		<tr>
			<th><a href="?order=id"><?= t('ID')?></a></th>
			<th><a href="?order=code"><?= t('Code')?></a></th>
			<th><a href="?order=name"><?= t('Name')?></a></th>
			<th><a href="?order=location"><?= t('Location')?></a></th>
		</tr>
		<?php while (!empty($items)){ $item=array_pop($items); $url = $base_url.$item->id.DS.'view' ?>
		<tr>
			<td><a href="<?= $url ?>"><?= substr($item->id,strlen($prefix)+1) ?></a></td>
			<td><a href="<?= $url ?>"><?= $item->code ?></a></td>
			<td><a href="<?= $url ?>"><?= $item->name ?></a></td>
			<td><a href="<?= getUrl('stock',$item->id.'/alter_location') ?>"><?= $item->location()->full() ?></a></td>
		</tr>
		<?php } // foreach item?>
	</table>
</fieldset>
<?php include '../common_templates/closure.php'; ?>
