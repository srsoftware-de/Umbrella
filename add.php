<?php include 'controller.php';

require_login('stock');



if ($company_id = param('company')){
	$prefix = 'company:'.$company_id.':';
} else $prefix = 'user:'.$user->id.':';

$locations = Location::load(['prefix'=>$prefix,'order'=>'name']);
if (empty($locations)) redirect(getUrl('stock','add_location?return_to='.location()));

$next_id = Item::getNextId($prefix);

$location = ($location_id = param('location_id')) ? $locations[$prefix.$location_id] : null;
$codes = Item::loadCodes($prefix);

if ($item_code = param('code')){
	if ($item_code != 'new_code'){
		$item = new Item();
		$item->patch(['id'=>$next_id,'code'=>$item_code,'location'=>$location]);
		$item->save();
		redirect(getUrl('stock'));
	}	
}

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
		</table>
		
		<?php } /* item_code = new_code */ else { ?>
		<?php } ?>
		
		<button type="submit"><?= t('Continue')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
