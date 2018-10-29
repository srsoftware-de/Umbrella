<?php include 'controller.php';

require_login('stock');

if ($id = param('id')){
	$item = Item::load(['ids'=>$id]);
	
	$parts = explode(':', $id);
	array_pop($parts);
	$prefix = implode(':', $parts);
	$locations = Location::load(['prefix'=>$prefix.':','order'=>'name']);
	
	if ($location_id = param('location_id')){
		$item->patch(['location'=>$locations[$location_id]])->save();
		redirect($base_url.$id.DS.'view');
	}
} else {
	error('No id passed to alter_location!');
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Alter location of ?',$item->name) ?></legend>
	<form method="POST">
		<select name="location_id">
		<?php foreach ($locations as $location) { ?>
			<option value="<?= $location->id ?>" <?= $location->id == $item->location_id ? 'selected="selected"':''?>><?= $location->name ?></option>
		<?php }?>
		</select>
		
		<button type="submit"><?= t('Continue')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
