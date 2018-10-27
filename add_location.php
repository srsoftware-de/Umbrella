<?php include 'controller.php';

require_login('stock');

if ($company_id = param('company')){
	$prefix = 'company:'.$company_id.':';
} else $prefix = 'user:'.$user->id.':';

$new_id = param('new_id');
$name = param('name');
if ($new_id && $name){
	$location = new Location();
	$location->patch($_POST)->patch(['new_id'=>$prefix.$new_id]);
	if ($loc_id = param('location_id')) $location->patch(['location_id'=>$prefix.$loc_id]);
	$location->save();
	
	redirect(param('return_to',getUrl('stock')));
}
$locations = Location::load(['prefix'=>$prefix,'order'=>'name']);
debug(['prefix'=>$prefix,'item_code'=>$item_code,'locations'=>$locations]);

warn('Location selection not implemented, yet');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add stock location') ?></legend>
	<form method="POST">
		<table>
			<tr>
				<th><?= t('Location id')?></th>
				<td><input type="text" name="new_id" <?= empty($new_id)?'':'value="'.$new_id.'" '?>/></td>
			</tr>
			<tr>
				<th><?= t('Where is this location?')?></th>
				<td>
					<select name="location_id">
					<?php foreach ($locations as $location){ $id = explode(':',$location->id); ?>
						<option value="<?= array_pop($id) ?>"><?= $location->name ?></option>
					<?php } // foreach location?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?= t('Name')?></th>
				<td><input type="text" name="name" <?= empty($name)?'':'value="'.$name.'" '?>/></td>
			</tr>
			<tr>
				<th><?= t('Description')?></th>
				<td><input type="text" name="description"/></td>
			</tr>
		</table>
		
		<button type="submit"><?= t('Continue')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
