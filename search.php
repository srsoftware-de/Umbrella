<?php include 'controller.php';

require_login('items');

$companies = request('company','json');

$company = null;
if ($company_id = param('company')){
	$company = $companies[$company_id];
}
?>
<fieldset class="companies">
<?php
	$order = param('order','code');
	$items = Item::load(['key'=>param('key'),'order'=>$order]); ?>
	<legend><?= t('Items') ?></legend>
	<table>
		<tr>
			<th>
				<a href="<?= location('*').'?company='.$company_id.'&order=code'?>"><?= t('Code') ?></a><br/>
			</th>
			<th>
				<a href="<?= location('*').'?company='.$company_id.'&order=name'?>"><?= t('Name') ?></a><br/>
				<a href="<?= location('*').'?company='.$company_id.'&order=unit_price'?>"><?= t('Price per unit')?></a>
			</th>
			<th>
				<a href="<?= location('*').'?company='.$company_id.'&order=tax'?>"><?= t('Tax') ?></a><br/>
				<a href="<?= location('*').'?company='.$company_id.'&order=unit'?>"><?= t('Unit')?></a>
			</th>
			<th>
			<?php if ($bookmark_service) { ?>
				<?= t('Tags')?>
			<?php }?><br/>
			<a href="<?= location('*').'?company='.$company_id.'&order=description'?>"><?= t('Description') ?></a>
			</th>
		</tr>
		<?php foreach ($items as $item) {
			$bookmark = $bookmark_service ? request('bookmark','json_get',['url'=>$item_base_url.$item->id.'/view']) : null;
		?>
		<tr>
			<td><?= $item->code ?></td>
			<td><?= $item->name ?></td>
			<td><?= $item->tax ?> %</td>
			<td>
			<?php if ($bookmark) foreach ($bookmark['tags'] as $tag) {
				if ($tag == 'company_'.$company['id'].'_items') continue;
				?>
				<a class="button" href="<?= $bookmark_service.$tag.'/view' ?>"><?= $tag ?></a>
			<?php }?>
			</td>
		</tr>
		<tr>
			<td></td>
			<td><?= ($item->unit_price/100).' '.$company['currency']?> /</td>
			<td><?= $item->unit ?></td>
			<td>
				<span class="right">
					<a class="symbol" href="<?= $item->id?>/edit"></a>
				</span>
				<?= str_replace("\n",'<br/>',$item->description) ?>
			</td>
		</tr>
		<?php }?>
	</table>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
