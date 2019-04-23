<?php include 'controller.php';

require_login('stock');

if ($key = param('key')){
	$items = Item::load(['search'=>$key]);
	if (!empty($items)){ ?>

<table>
	<tr>
		<th><?= t('ID') ?><th>
		<th><?= t('Code') ?></th>
		<th><?= t('Name') ?></th>
		<th><?= t('Location') ?></th>
	</tr>
	<?php foreach ($items as $item) { ?>
	<tr>
		<td><a href="<?= $base_url.$item->id.DS.'view'?>"><?= emphasize($item->id,$key) ?></a><td>
		<td><a href="<?= $base_url.$item->id.DS.'view'?>"><?= emphasize($item->code,$key) ?></a></td>
		<td><a href="<?= $base_url.$item->id.DS.'view'?>"><?= emphasize($item->name,$key) ?></a></td>
		<td><a href="<?= $base_url.$item->id.DS.'view'?>"><?= emphasize($item->location()->name,$key) ?></a></td>
	</tr>
	<?php } ?>
</table>
	<?php }
}