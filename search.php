<?php include 'controller.php';

require_login('items');

$key = param('key');
$companies = request('company','json');
$items = Item::load(['search'=>$key,'order'=>$order]); ?>
<?php if (!empty($items)) { ?>
<table>
	<tr>
		<th>
			<?= t('Code') ?><br/>
		</th>
		<th>
			<?= t('Name') ?><br/>
			<?= t('Price per unit')?>
		</th>
		<th>
			<?= t('Tax') ?><br/>
			<?= t('Unit')?>
		</th>
		<th>
			<?= t('Description') ?><br/>
		</th>
	</tr>
	<?php foreach ($items as $item) { ?>
	<tr>
		<td><?= emphasize($item->code,$key) ?></td>
		<td><?= emphasize($item->name,$key) ?></td>
		<td><?= emphasize($item->tax,$key) ?> %</td>
		<td rowspan="2">
			<span class="right">
				<a class="symbol" href="<?= $item->id?>/edit"></a>
			</span>
			<?= emphasize(str_replace("\n",'<br/>',$item->description),$key) ?>
		</td>
	</tr>
	<tr>
		<td></td>
		<td><?= ($item->unit_price/100).' '.$companies[$item->company_id]['currency']?> /</td>
		<td><?= emphasize($item->unit,$key) ?></td>
	</tr>
	<?php }?>
</table>
<?php } // items not empty ?>
