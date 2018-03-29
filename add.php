<?php $title = 'Umbrella Item Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');

$companies = request('company','json');
$company_id =param('company');
if (!isset($companies[$company_id])){
	error('Was not able to finde requested company!');
} else {
	$company = $companies[$company_id];
}

if ($item_data = param('item')){
	$item = new Item();
	$item_data['unit_price'] *= 100;
	$item->patch($item_data);
	$item->save();

	$tags_raw = explode(' ',param('tags'));

	if (isset($services['bookmark'])){
		$tags = ['company_'.$company_id.'_items'];
		foreach ($tags_raw as $tag){
			if (trim($tag) == '') continue;
			$tags[] = $tag;
		}

		$item_url = getUrl('items',$item->id.'/view');
		request('bookmark','add',['url'=>$item_url,'tags'=>$tags,'comment'=>$item->description]);
	}
	redirect('index?company='.$company_id);
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Add entry to items of ?',$company['name'])?></legend>
		<input	type="hidden"	name="item[company_id]"	value="<?= $company['id']?>" />
		<fieldset>
			<legend><?= t('Item code')?></legend>
			<input	type="text"	name="item[code]" />
		</fieldset>
		<fieldset>
			<legend><?= t('Item name')?></legend>
			<input	type="text"	name="item[name]" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description')?></legend>
			<textarea	name="item[description]" ><?= $item->description ?></textarea>
		</fieldset>
		<fieldset>
			<legend><?= t('Unit')?></legend>
			<input	type="text"	name="item[unit]" />
		</fieldset>
		<fieldset>
			<legend><?= t('Price per unit')?></legend>
			<input	type="text"	name="item[unit_price]" /> <?= $company['currency']?>
		</fieldset>
		<fieldset>
			<legend><?= t('Tax')?></legend>
			<input	type="text"	name="item[tax]" /> %
		</fieldset>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input	type="text"	name="tags" />
		</fieldset>
		
		<button type="submit"><?= t('Save')?></button>
	</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
