<?php include 'controller.php';

require_login('stock');

if ($item_id = param('id')){
	$item = Item::load(['ids'=>$item_id]);
} else error('No item id given!');

if (($name = param('name')) && ($value = param('value'))){
	$type = param('type');
	$property = new Property();
	$property->patch(['type'=>$type,'name'=>$name])->save();
	
	$item_prop = new ItemProperty();
	$item_prop->patch(['property'=>$property,'value'=>$value,'item_id'=>$item_id]);
	$item_prop->save();
	redirect(getUrl('stock'));
}


$item_props = $item->properties();
$related_props = Property::getRelated($item->code);
debug(['item'=>$item,'related'=>$related_props]);
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<table>
		<tr>
			<th><?= t('Name of new property')?></th>
			<th><?= t('Type of new property') ?></th>
			<th><?= t('Value of new property') ?></th>
		</tr>
		<?php foreach ($related_props as $prop) { ?>
		<tr>
			<td>
				<?= $prop->name ?>
			</td>
			<td>
				<?php switch ($prop->type){					
					case 1: echo t('Integer'); break;
					case 2: echo t('Float'); break;
					case 3: echo t('Boolean'); break;
					default: echo t('String'); break;
				}	?>
			</td>
			<td>
				<input type="text" name="related[<?= $prop->id ?>]" value="<?= isset($item_props[$prop->id]) ? $item_props[$prop->id]->value : ''?>" />
			</td>
		</tr>		
		<?php } // foreach related property?>
		
		
		
		<tr>
			<td>
				<input type="text" name="name" />
			</td>
			<td>
				<select name="type">
					<option value="<?= TYPE_STRING ?>"><?= t('String') ?></option>
					<option value="<?= TYPE_INT ?>"><?= t('Integer') ?></option>
					<option value="<?= TYPE_FLOAT ?>"><?= t('Float') ?></option>
					<option value="<?= TYPE_BOOL ?>"><?= t('Boolean') ?></option>
				</select>
			</td>
			<td><input type="text" name="value" /></td>
		</tr>
	</table>
	<button type="submit"><?= t('Save')?></button>
</form>
<?php include '../common_templates/closure.php'; ?>
