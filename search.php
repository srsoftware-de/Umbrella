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
		<?php foreach ($vcards as $vcard){
			$addresses = $vcard->addresses();
			$emails    = $vcard->emails();
			$phones    = $vcard->phones();
		?>
		<tr>
			<td><?= emphasize(isset($vcard->FN) ? $vcard->FN :'',$key) ?></td>
			<td><?= emphasize($vcard->name(BEAUTY),$key) ?></td>
			<td>
			<?php while (!empty($addresses)) { ?>
				<p><?= emphasize(array_shift($addresses)->format(' / '),$key) ?></p>
			<?php } ?>
			</td>
			<td>
			<?php while (!empty($emails)) { ?>
				<p><?= emphasize(array_shift($emails),$key) ?></p>
			<?php } ?>
			</td>
			<td>
			<?php while(!empty($phones)) { ?>
				<p><?= emphasize(array_shift($phones),$key) ?></p>
			<?php } ?>
			</td>
			<td>
				<a class="symbol" title="<?= t('download') ?>" href="<?= $url.$vcard->id?>/download"></a>
				<a class="symbol" title="<?= t('edit') ?>" href="<?= $url.$vcard->id ?>/edit"></a>
				<a class="symbol" title="<?= t('assign with me') ?>" href="<?= $url.$vcard->id?>/assign_with_me"></a>
			</td>
		</tr>
		<?php } ?>
	</table>
	<?php }
}