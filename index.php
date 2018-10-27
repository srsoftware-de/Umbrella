<?php include 'controller.php';

require_login('stock');

if ($company_id = param('company')){
	$prefix = 'company:'.$company_id.':';
} else $prefix = 'user:'.$user->id.':';

$items = Item::load(['prefix'=>$prefix]);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table class="stock">
	<tr>
		<th><?= t('ID')?></th>
		<th><?= t('Code')?></th>
		<th><?= t('Location')?></th>
		<th colspan="2"><?= t('Properties')?></th>
		<th><?= t('Actions')?></th>
	</tr>
	<?php while (!empty($items)){
		$item = array_pop($items); 
		$properties = $item->properties();
		$prop = empty($properties) ? null : array_shift($properties); ?>
	<tr class="first">
		<td><?= substr($item->id,strlen($prefix)) ?></td>
		<td><?= $item->code ?></td>
		<td><?= $item->location()->full() ?></td>
		<td>
			<?= empty($prop)?'':$prop->name() ?>
		</td>
		<td>
			<?= empty($prop)?'':$prop->value ?>&nbsp;<?= empty($prop)?'':$prop->unit() ?>
		</td>
		<td>
			<a class="button" href="<?= getUrl('stock',$item->id.'/add_property'.($company_id?'?company='.$company_id:''))?>"><?= t('Add property')?></a>
		</td>
	</tr>
	<?php $first = true; while (!empty($properties)) { 
	$prop = array_shift($properties); ?>
	<tr>
		<td></td>
		<td colspan="2"><?= $first ? $item->name : ''?></td>
		<td>
			<?= empty($prop)?'':$prop->name() ?>
		</td>
		<td colspan="2">
			<?= empty($prop)?'':$prop->value ?>&nbsp;<?= empty($prop)?'':$prop->unit() ?>
		</td>
	</tr>
	<?php $first = false; } // while properties not empty ?>
	<?php } // foreach item?>
</table>

<?php include '../common_templates/closure.php'; ?>
