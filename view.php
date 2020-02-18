<?php include 'controller.php';

require_login('stock');

$users = [ $user->id ];

if ($item_id = param('id')){
	$parts = explode(':', $item_id);
	$realm = $parts[0];
	$realm_id = $parts[1];
	switch ($realm){
		case 'company':
			$company = request($realm,'json',['ids'=>$realm_id,'users'=>true]);
			assert(!empty($company),t('You are not allowed to access items of this ?',$realm));
			$users = $company['users'];
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

$index_url = $base_url.$prefix.DS.'index';
$files_path = empty($services['files']) ? null : str_replace(':', DS,$prefix).DS.'stock'.DS.'item:'.$num;

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= $item->name ?></legend>
<table class="stock">
	<tr>
		<th><a href="<?= $base_url.$prefix.':'.($num-1).DS.'view' ?>">&lt;</a> <?= t('ID')?> <a href="<?= $base_url.$prefix.':'.($num+1).DS.'view' ?>">&gt;</a></th>
		<th><?= t('Code')?></th>
		<td colspan="2">
			<a class="button" href="<?= $index_url ?>"><?= t('Stock index')?></a>
			<?php if (!empty($files_path)) { ?><a class="button" href="<?= getUrl('files','?path='.$files_path)?>"><?= t('Files')?></a><?php } ?>
			<a class="button" href="<?= getUrl('stock',$item_id.'/edit')?>"><?= t('Edit')?></a>
		</td>
	</tr>
	<tr>
		<th colspan="2"><?= t('Location')?></th>
		<th colspan="2"><?= t('Properties')?></th>
	</tr>

	<?php
		$properties = $item->properties();
		$prop = empty($properties) ? null : array_shift($properties); ?>
	<tr class="first">
		<td><?= $item_id ?></td>
		<td><?= $item->code ?></td>
		<td>
			<?= empty($prop)?'':$prop->name() ?>
		</td>
		<td>
			<?= empty($prop)?'':$prop->value ?>&nbsp;<?= empty($prop)?'':$prop->unit() ?>
		</td>
	</tr>
	<?php $first = true; while (!empty($properties)) {
	$prop = array_shift($properties); ?>
	<tr>
		<td colspan="2">
			<?php if ($first) { ?>
			<a href="<?= getUrl('stock',$item->id.'/alter_location') ?>"><?= $item->location()->full() ?></a>
			<?php } ?>
		</td>
		<td>
			<?= empty($prop)?'':$prop->name() ?>
		</td>
		<td>
			<?= empty($prop)?'':$prop->value ?>&nbsp;<?= empty($prop)?'':$prop->unit() ?>
		</td>
	</tr>
	<?php $first = false; } // while properties not empty ?>

</table>
</fieldset>
<?php if (!empty($files_path)){
	$file_list = request('files','index',['format'=>'json','path'=>$files_path]);
	$file_base_url = getUrl('files','download?file=');
	if (!empty($file_list['files'])){ ?>
	<fieldset>
		<legend><?= t('Files')?></legend>
		<?php foreach ($file_list['files'] as $name => $path){ ?>
		<a href="<?= $file_base_url.$path ?>"><span class="symbol"></span> <?= $name ?></a>
		<?php } // foreach files?>
	</fieldset>
	<?php } // not empty?>
<?php } // files service detected ?>
<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'stock:'.$item->id,'context'=>$item->name,'users'=>$users],false,NO_CONVERSION);

include '../common_templates/closure.php'; ?>
