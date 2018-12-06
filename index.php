<?php include 'controller.php';

require_login('items');

$companies = request('company','json');
if (empty($companies)) warn('In order to add items to for you business, you have to create a company first. Go to the company section.');
$company = null;
if ($company_id = param('company')){
	$company = $companies[$company_id];
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset class="companies">
<?php if (!$company){ ?>
	<legend><?= t('Select a company') ?></legend>
	<?php foreach ($companies as $id => $company){ ?>
	<a class="button" href="?company=<?= $id ?>"><?= $company['name']?></a>
	<?php } ?>

<?php } else {
	$order = param('order','code');
	$items = Item::load(['company_id'=>$company_id,'order'=>$order]);
	$bookmark_service = isset($services['bookmark']) ? getUrl('bookmark') : null;
	$item_base_url = $bookmark_service ? getUrl('items') : null;
	?>
	<legend><?= t('Items of ?',$company['name']) ?></legend>
	<table class="items">
		<tr>
			<th><a href="<?= location('*').'?company='.$company_id.'&order=code'?>"><?= t('Code') ?></a></th>
			<th><a href="<?= location('*').'?company='.$company_id.'&order=name'?>"><?= t('Name') ?></a></th>
			<th><a href="<?= location('*').'?company='.$company_id.'&order=description'?>"><?= t('Description') ?></a></th>
			<th><a href="<?= location('*').'?company='.$company_id.'&order=unit_price'?>"><?= t('Price per unit')?></a></th>
			<th><a href="<?= location('*').'?company='.$company_id.'&order=unit'?>"><?= t('Unit')?></a></th>
			<th><a href="<?= location('*').'?company='.$company_id.'&order=tax'?>"><?= t('Tax') ?></a></th>
			<th><?= $bookmark_service ? t('Tags') : '' ?></th>
			<th><?= t('Actions')?></th>
		</tr>
		<?php foreach ($items as $item) {
			$bookmark = $bookmark_service ? request('bookmark','json_get',['url'=>$item_base_url.$item->id.'/view']) : null;
		?>
		<tr>
			<td><?= $item->code ?></td>
			<td><?= $item->name ?></td>
			<td><?= str_replace("\n",'<br/>',$item->description) ?></td>
			<td><?= ($item->unit_price/100).' '.$company['currency']?> /</td>
			<td><?= $item->unit ?></td>
			<td><?= $item->tax ?> %</td>
			<td>
			<?php if ($bookmark) foreach ($bookmark['tags'] as $tag) {
				if ($tag == 'company_'.$company['id'].'_items') continue;
				?>
				<a class="button" href="<?= $bookmark_service.$tag.'/view' ?>"><?= $tag ?></a>
			<?php }?>
			</td>
			<td><a class="symbol" href="<?= $item->id?>/edit"></a></td>
		</tr>
		<?php }?>
	</table>
<?php } ?>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
