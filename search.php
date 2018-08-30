<?php include 'controller.php';

require_login('contact');

if ($key = param('key')){
	$vcards = VCard::load(['key'=>$key]);
	
	if (!empty($vcards)){ 
	$url=getUrl('contact');?>
	<table>
		<tr>
			<th><?= t('short') ?></th>
			<th><?= t('name') ?></th>
			<th><?= t('addresses') ?></th>
			<th><?= t('emails') ?></th>
			<th><?= t('phones') ?></th>
			<th><?= t('actions') ?></th>
		</tr>
		<?php foreach ($vcards as $id => $vcard){
			$addresses = $vcard->addresses();
			$emails    = $vcard->emails();
			$phones    = $vcard->phones();
		?>
		<tr>
			<td><?= isset($vcard->FN) ? $vcard->FN :'' ?></td>
			<td><?= $vcard->name(BEAUTY) ?></td>
			<td>
			<?php while (!empty($addresses)) { ?>
				<p><?= array_shift($addresses)->format(' / ') ?></p>
			<?php } ?>
			</td>
			<td>
			<?php while (!empty($emails)) { ?>
				<p><?= array_shift($emails) ?></p>
			<?php } ?>
			</td>
			<td>
			<?php while(!empty($phones)) { ?>
				<p><?= array_shift($phones) ?></p>
			<?php } ?>
			</td>
			<td>
				<a class="symbol" title="<?= t('download') ?>" href="<?= $url.$id?>/download"></a>
				<a class="symbol" title="<?= t('edit') ?>" href="<?= $url.$id ?>/edit"></a>
				<a class="symbol" title="<?= t('assign with me') ?>" href="<?= $url.$id?>/assign_with_me"></a>
			</td>
		</tr>
		<?php } ?>
	</table>
	<?php }
}