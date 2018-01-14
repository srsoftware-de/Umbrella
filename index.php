<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login('contact');

$vcards = VCard::load();
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Contacts') ?></legend>
	<table>
		<tr>
			<th><?= t('short') ?></th>
			<th><?= t('name') ?></th>
			<th><?= t('addresses') ?></th>
			<th><?= t('phones') ?></th>
			<th><?= t('emails') ?></th>
			<th><?= t('actions') ?></th>
			
		</tr>
		<?php foreach ($vcards as $id => $vcard){ 
			$addresses = $vcard->addresses();
			$emails    = $vcard->emails();
			$phones    = $vcard->phones();
		?>
		<tr>
			<td><?= isset($vcard->fields['FN']) ? $vcard->fields['FN']['val'] :'' ?></td>
			<td><?= (string)$vcard->name() ?></td>
			<td>
			<?php while (!empty($addresses)) { ?>
				<p><?= array_shift($addresses)->get() ?></p>
			<?php } ?>
			</td>
			<td>
			<?php while (!empty($emails)) { ?>
				<p><?= array_shift($emails)['val'] ?></p>
			<?php } ?>
			</td>
			<td>
			<?php while(!empty($phones)) { ?>
				<p><?= array_shift($phones)['val'] ?></p>
			<?php } ?>
			</td>
			<td>
				<a class="symbol" title="download" href="<?= $id?>/download"></a>
				<a class="symbol" title="edit" href="<?= $id?>/edit"></a>
				<a class="symbol" title="assign with me" href="<?= $id?>/assign_with_me"></a>
			</td>
		</tr>
		<?php } ?>
	</table>
</fieldset>
<?php include '../common_templates/closure.php'; ?>
