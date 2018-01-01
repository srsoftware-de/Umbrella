<?php $title = 'Umbrella Item Management';

include '../bootstrap.php';
include 'controller.php';

require_login('items');

$companies = request('company','json');

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
	$items = Item::load(['company_id'=>$company_id]);
	$bookmark_service = isset($services['bookmark']) ? getUrl('bookmark') : null; 
	$item_base_url = $bookmark_service ? getUrl('items') : null;
	?>
	<legend><?= t('Items of ?',$company['name']) ?></legend>
	<table>
		<tr>
			<th><?= t('Code') ?><br/></th>
			<th><?= t('Name') ?><br/><?= t('Price per unit')?></th>
			<th><?= t('Tax') ?><br/><?= t('Unit')?></th>
			<th>
			<?php if ($bookmark_service) { ?>
				<?= t('Tags')?>
			<?php }?><br/><?= t('Description') ?>
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
				<?= $item->description ?>
			</td>
		</tr>
		<?php }?>
	</table>
<?php } ?>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
