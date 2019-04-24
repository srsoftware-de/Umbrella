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

$locations = Location::load(['prefix'=>$prefix.':','order'=>'name']);

if ($location_id = param('location_id')){
	$item->patch(['location_id'=>$location_id])->save();
	redirect($base_url.$item_id.DS.'view');
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Alter location of ◊',$item->name) ?></legend>
	<a class="button" href="<?= $base_url.$prefix.DS.'add_location' ?>"><?= t('Add stock location') ?></a>
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
