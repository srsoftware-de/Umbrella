<?php include 'controller.php';

require_login('stock');

$companies = isset($services['company']) ? request('company','json') : null;

$prefix = param('id');
if (!$prefix) $prefix = 'user:'.$user->id;

$locations = Location::load(['prefix'=>$prefix.':','order'=>'name']);

if (empty($locations)) redirect($base_url.$prefix.DS.'add_location');

$next_id = Item::getNextId($prefix.':');

$location = ($location_id = param('location_id')) ? $locations[$prefix.':'.$location_id] : null;
$codes = Item::loadCodes($prefix.':');

$selection = param('code_selection');
$code = $selection == 'new_code' ? param('new_code') : $selection;

if (!empty($code)){
	$item = new Item();
	$item->patch(['id'=>$next_id,'code'=>$code,'name'=>param('name'),'location'=>$location]);
	$item->save();
	redirect(getUrl('stock',$item->id.'/edit'.($company_id?'?company='.$company_id:'')));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add new item to stock') ?></legend>
	<form method="POST">
		<table>
			<tr>
				<th><?= t('Item Id')?></th>
				<td colspan="2"><?= $next_id ?></td>
			</tr>
			<tr>
				<th><?= t('Select item code or enter new')?></th>
				<td>
					<select name="code_selection">
						<option value="new_code"><?= t('New code')?></option>
						<?php foreach ($codes as $code) { ?>
						<option value="<?= reset($code) ?>"><?= reset($code) ?></option>
						<?php } ?>
					</select>
					<input type="text" name="new_code"/></td>
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
					<a class="button" href="<?= $base_url.$prefix.DS.'add_location?return_to='.location('*'); ?>"><?= t('Add stock location')?></a>
				</td>
			</tr>
			<tr>
				<th><?= t('Enter name/description')?></th>
				<td>
					<input type="text" name="name"/></td>
				<td></td>
			</tr>
		</table>
		<button type="submit"><?= t('Add item')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
