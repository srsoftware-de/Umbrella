<?php include 'controller.php';

require_login('stock');

if ($item_id = param('id')){
	$parts = explode(':', $item_id);
	$realm = $parts[0];
	$realm_id = $parts[1];
	switch ($realm){
		case 'company':
			$company = request($realm,'json',['ids'=>$realm_id]);
			assert(!empty($company),t('You are not allowed to access items of this ?',$realm));
			break;
		case 'user':
			assert($realm_id == $user->id,t('You are not allowed to access items of this ?',$realm));
			break;
	}
	$item = Item::load(['ids'=>$item_id]);
	$num = array_pop($parts);
	$prefix = implode(':', $parts);
	if (!$item) redirect($base_url.$prefix.DS.'add');
} else error('No item id supplied!');

$item_props = $item->properties();
$related_props = Property::getRelated($item);
$all_props = Property::load();

$redirect = null;
$company_id = param('company');

if ($new_prop = param('new_prop')){
	if (!empty($new_prop['name']) && $new_prop['type'] !== null && !empty($new_prop['value'])){
		$property = new Property();
		$property->patch($new_prop)->save();

		$item_prop = new ItemProperty();
		$item_prop->patch(['property'=>$property,'value'=>$new_prop['value'],'item_id'=>$item_id]);
		$item_prop->save();

		$redirect = $base_url.$item_id.DS.'view';
	}
}
$selected_prop = param('selected_prop');
$selected_value = param('selected_value');
if (!empty($selected_prop) && !empty($selected_value)){
	$property = $all_props[$selected_prop];

	$item_prop = new ItemProperty();
	$item_prop->patch(['property'=>$property,'value'=>$selected_value,'item_id'=>$item_id]);
	$item_prop->save();
	$redirect = $base_url.$item_id.DS.'view';
}

$related = param('related');
foreach ($related as $prop_id => $value){
	$item_prop = new ItemProperty();
	$item_prop->patch(['property'=>$all_props[$prop_id],'value'=>$value,'item_id'=>$item_id]);
	if ($value != ''){
		$item_prop->save();
	} else { // set property
		if (array_key_exists($prop_id, $item->properties))	$item_prop->delete();  // remove property
	}
	$redirect = $base_url.$item_id.DS.'view';
}

if ($name = param('name')) $item->patch(['name'=>$name]);
if ($code = param('code')) $item->patch(['code'=>$code]);
if (!empty($item->dirty)) $item->save();

if ($redirect) redirect($redirect);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';?>

<form method="POST">
	<fieldset>
		<legend><?= t('Item')?></legend>
		<table>
			<tr>
				<th><?= t('Code')?></th>
				<td><input type="text" name="code" value="<?= $item->code?>" /></td>
			</tr>
			<tr>
				<th><?= t('Name')?></th>
				<td><input type="text" name="name" value="<?= $item->name?>" /></td>
			</tr>
			<tr>
				<th><?= t('Location')?></th>
				<td><?= $item->location()->full() ?></td>
			</tr>

		</table>
	</fieldset>
	<fieldset>
		<legend><?= t('Properties')?></legend>
		<table>
			<?php if (!empty($related_props)) { ?>
			<tr>
				<th><?= t('Properties of ◊',$item->code)?></th>
				<th><?= t('Type of property') ?></th>
				<th><?= t('Value') ?></th>
				<th><?= t('Unit') ?></th>
			</tr>
			<?php foreach ($related_props as $prop) { ?>
			<tr>
				<td>
					<?= $prop->name ?>
				</td>
				<td>
					<?= t("prop_type_$prop->type"); ?>
				</td>
				<td>
					<input type="text" name="related[<?= $prop->id ?>]" value="<?= isset($item_props[$prop->id]) ? $item_props[$prop->id]->value : ''?>" />
				</td>
				<td>
					<?= $prop->unit; ?> <span class="prop_action symbol"><a href="<?= $base_url ?>edit_property/<?= $prop->id ?>?redirect=<?= location('*') ?>"></a></span>
				</td>
			</tr>
			<?php } // foreach related property?>
			<?php } // related not empty ?>

			<?php if (!empty($all_props)) { ?>
			<tr>
				<th colspan="2"><?= t('Add existing property (from other items)')?></th>
				<th><?= t('Value of new property') ?></th>
				<th></th>
			</tr>

			<tr>
				<td colspan="2">
					<select name="selected_prop">
					<?php foreach ($all_props as $prop) { if (array_key_exists($prop->id,$related_props)) continue; ?>
						<option value="<?= $prop->id ?>"><?= $prop->name ?> (<?= t("prop_type_$prop->type"); ?>, <?= $prop->unit ?>)</option>
					<?php } // foreach all_props?>
					</select>
				</td>
				<td><input type="text" name="selected_value" /></td>
				<td><?= t('(automatically assigned)')?></td>
			</tr>

			<?php } // all props not empty?>

			<tr>
				<th><?= t('Create new property')?></th>
				<th><?= t('Type of new property') ?></th>
				<th><?= t('Value of new property') ?></th>
				<th><?= t('Unit') ?></th>
			</tr>

			<tr>
				<td>
					<input type="text" name="new_prop[name]" value="<?= $new_prop['name'] ?>" />
				</td>
				<td>
					<select name="new_prop[type]">
						<option value="<?= TYPE_STRING ?>"><?= t('String') ?></option>
						<option value="<?= TYPE_INT ?>"><?= t('Integer') ?></option>
						<option value="<?= TYPE_FLOAT ?>"><?= t('Float') ?></option>
						<option value="<?= TYPE_BOOL ?>"><?= t('Boolean') ?></option>
					</select>
				</td>
				<td><input type="text" name="new_prop[value]" value="<?= $new_prop['value'] ?>" /></td>
				<td><input type="text" name="new_prop[unit]" value="<?= $new_prop['unit'] ?>" /></td>
			</tr>
		</table>
	</fieldset>

	<button type="submit"><?= t('Save')?></button>
</form>

<?php include '../common_templates/closure.php'; ?>
