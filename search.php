<?php include 'controller.php';

require_login('items');

$companies = request('company','json');
$items = Item::load(['search'=>param('key'),'order'=>$order]); ?>
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
		<td><?= $item->code ?></td>
		<td><?= $item->name ?></td>
		<td><?= $item->tax ?> %</td>
		<td rowspan="2">
			<span class="right">
				<a class="symbol" href="<?= $item->id?>/edit"></a>
			</span>
			<?= str_replace("\n",'<br/>',$item->description) ?>
		</td>
	</tr>
	<tr>
		<td></td>
		<td><?= ($item->unit_price/100).' '.$companies[$item->company_id]['currency']?> /</td>
		<td><?= $item->unit ?></td>
	</tr>
	<?php }?>
</table>
<?php } // items not empty ?>
