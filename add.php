<?php

include '../bootstrap.php';
include 'controller.php';

require_login('stock');

const TYPE_STRING = 0;
const TYPE_INT    = 1;
const TYPE_FLOAT  = 2;
const TYPE_BOOL   = 3;

if ($company_id = param('company')){
	$prefix = 'company:'.$company_id.':';
} else $prefix = 'user:'.$user->id.':';

$locations = Location::load(['prefix'=>$prefix,'order'=>'name']);
if (empty($locations)) redirect(getUrl('stock','add_location?return_to='.location()));

$next_id = Item::getNextId($prefix);

$location = ($location_id = param('location_id')) ? $locations[$prefix.$location_id] : null;

if ($item_code = param('code')){
	debug(['prefix'=>$prefix,'item_code'=>$item_code,'locations'=>$locations,'POST'=>$_POST]);
	
	if ($item_code == 'new_code'){
		
	} else {
		$item_type = ItemType::load(['prefix'=>$prefix,'code'=>$item_code]);
		if ($item_type == null) $item_type = new ItemType();
		
		if ($type_prop_name = param('type_prop_name')){
			$property = Property::load(['name'=>$type_prop_name]);
			if ($property == null) $property = new Property();
			$property->patch(['name'=>$type_prop_name,'type'=>param('type_prop_type',0)])->save();
			
			$item_type->patch(['property'=>$property,'value'=>param('type_prop_value'),'code'=>$item_code])->save();
		}
		
		$item = new Item();
		$item->patch(['id'=>$next_id,'code'=>$item_code,'location'=>$location]);
		$item->save();
		redirect(getUrl('stock'));
	}
	
} else $item_types = ItemType::load(['prefix'=>$prefix]);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add new item to stock') ?></legend>
	<form method="POST">
		<?php if (empty($item_code)) { ?>
		<label>
			<?= t('Select item code')?>
			<select name="code">
				<option value="new_code"><?= t('New code')?></option>
				<?php foreach ($item_types as $item_type) { ?>
				<option value="<?= $item_type->code ?>"><?= $item_type->code ?></option>
				<?php } ?>
			</select>
		</label>
		<?php } /* empty item_code */ elseif ($item_code == 'new_code') { ?>
		<table>
			<tr>
				<th><?= t('Item Id')?></th>
				<td colspan="2"><?= $next_id ?></td>
			</tr>
			<tr>
				<th><?= t('Enter new item code')?></th>
				<td><input type="text" name="code"/></td>
				<td></td>
			</tr>
			<tr>
				<th><?= t('Select location of item')?></th>
				<td>
					<select name="location_id">
					<?php foreach ($locations as $location){ $id = explode(':',$location->id); ?>
						<option value="<?= array_pop($id) ?>"><?= $location->name ?></option>
					<?php } // foreach location?>
					</select>
				</td>
				<td>
					<a class="button" href="<?= getUrl('stock','add_location?return_to='.location()); ?>"><?= t('Add stock location')?></a>
				</td>
			</tr>
			<tr>
				<th colspan="3"><?= t('Common properties of all Objects of this type')?></th>
			</tr>
			<tr>
				<th><?= t('Name of new property')?></th>
				<th><?= t('Type of new property')?></th>
				<th><?= t('Value of new property')?></th>
			</tr>
			<tr>
				<td><input type="text" name="type_prop_name" /></td>
				<td>
					<select name="type_prop_type">
						<option value="<?= TYPE_STRING ?>"><?= t('String')?></option>
						<option value="<?= TYPE_INT ?>"><?= t('Integer')?></option>
						<option value="<?= TYPE_FLOAT ?>"><?= t('Float')?></option>
						<option value="<?= TYPE_BOOL ?>"><?= t('Boolean')?></option>
					</select>
				</td>
				<td><input type="text" name="type_prop_value"/></td>
			</tr>
			
			<tr>
				<th colspan="3"><?= t('Individual properties of this Object')?></th>
			</tr>
			<tr>
				<th><?= t('Name of new property')?></th>
				<th><?= t('Type of new property')?></th>
				<th><?= t('Value of new property')?></th>
			</tr>
			<tr>
				<td><input type="text" name="obj_prop_name" /></td>
				<td>
					<select name="obj_prop_type">
						<option value="<?= TYPE_STRING ?>"><?= t('String')?></option>
						<option value="<?= TYPE_INT ?>"><?= t('Integer')?></option>
						<option value="<?= TYPE_FLOAT ?>"><?= t('Float')?></option>
						<option value="<?= TYPE_BOOL ?>"><?= t('Boolean')?></option>
					</select>
				</td>
				<td><input type="text" name="obj_prop_value"/></td>
			</tr>
		</table>
		
		<?php } /* item_code = new_code */ else { ?>
		<?php } ?>
		
		<button type="submit"><?= t('Continue')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
