<?php include 'controller.php';

require_login('items');

if ($id = param('id')){
	$item = Item::load(['ids'=>$id,'single'=>true]);

	if ($item_data = param('item')){
        	$item_data['unit_price'] *= 100;
        	$item->patch($item_data);
        	$item->save();
        	
        	$tags_raw = explode(' ',param('tags'));
        	
        	if (isset($services['bookmark'])){
        	        $tags = ['company_'.$item->company_id.'_items'];
        	        foreach ($tags_raw as $tag){
        	                if (trim($tag) == '') continue;
        	                $tags[] = $tag;
        	        }
                
        	        $item_url = getUrl('items',$item->id.'/view');
        	        request('bookmark','add',['url'=>$item_url,'tags'=>$tags,'comment'=>$item->description]);
        	}
        	redirect('..?company='.$item->company_id);
	}
} else error('No item id passed to edit method!');

$bookmark = isset($services['bookmark']) ? request('bookmark','json_get?id='.sha1(getUrl('items',$id.'/view'))) : null; 

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Edit item of ?',$item->company['name'])?></legend>
		<fieldset>
			<legend><?= t('Item code')?></legend>
			<input	type="text"	name="item[code]" value="<?= $item->code ?>"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Item name')?></legend>
			<input	type="text"	name="item[name]" value="<?= $item->name ?>"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Description')?></legend>
			<textarea	name="item[description]" ><?= $item->description ?></textarea>
		</fieldset>
		<fieldset>
			<legend><?= t('Unit')?></legend>
			<input	type="text"	name="item[unit]" value="<?= $item->unit ?>"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Price per unit')?></legend>
			<input	type="text"	name="item[unit_price]" value="<?= $item->unit_price/100 ?>"/> <?= $company['currency']?>
		</fieldset>
		<fieldset>
			<legend><?= t('Tax')?></legend>
			<input	type="text"	name="item[tax]" value="<?= $item->tax ?>"/> %
		</fieldset>
		<?php if ($bookmark) { ?>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input	type="text"	name="tags" value="<?= implode(' ',$bookmark['tags'])?>"/>
		</fieldset>
		<?php } ?>
		<button type="submit"><?= t('Submit')?></button>
	</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
