<?php include 'controller.php';

require_login('stock');

if ($item_id = param('id')){
	$item = Item::load(['ids'=>$prefix.$item_id]);	
} else error('No item id supplied!');

$parts = explode(':', $item_id);
array_pop($parts);
$index_url = $base_url.implode(':', $parts).'/index';

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<h2><?= $item->name ?></h2>
<table class="stock">
	<tr>
		<th><?= t('ID')?></th>
		<th><?= t('Code')?></th>
		<td colspan="2">
			<a class="button" href="<?= $index_url ?>"><?= t('Stock index')?></a>
			<a class="button" href="<?= getUrl('stock',$item_id.'/add_property'.($company_id?'?company='.$company_id:''))?>"><?= t('Add property')?></a>
		</td>
	</tr>
	<tr>
		<th colspan="2"><?= t('Location')?></th>
		<th colspan="2"><?= t('Properties')?></th>
	</tr>
	
	<?php  
		$properties = $item->properties();
		$prop = empty($properties) ? null : array_shift($properties); ?>
	<tr class="first">
		<td><?= $item_id ?></td>
		<td><?= $item->code ?></td>
		<td>
			<?= empty($prop)?'':$prop->name() ?>
		</td>
		<td>
			<?= empty($prop)?'':$prop->value ?>&nbsp;<?= empty($prop)?'':$prop->unit() ?>
		</td>
	</tr>
	<?php $first = true; while (!empty($properties)) { 
	$prop = array_shift($properties); ?>
	<tr>
		<td colspan="2">
			<?php if ($first) { ?>
			<a href="<?= getUrl('stock',$item->id.'/alter_location'.($company_id?'?company='.$company_id:'')) ?>"><?= $item->location()->full() ?></a>
			<?php } ?>
		</td>
		<td>
			<?= empty($prop)?'':$prop->name() ?>
		</td>
		<td>
			<?= empty($prop)?'':$prop->value ?>&nbsp;<?= empty($prop)?'':$prop->unit() ?>
		</td>
	</tr>
	<?php $first = false; } // while properties not empty ?>
	
</table>

<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'stock:'.$item->id],false,NO_CONVERSION); 

include '../common_templates/closure.php'; ?>
