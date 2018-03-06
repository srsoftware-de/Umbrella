<?php
include '../bootstrap.php';
include 'controller.php';

require_login('contact');

$title = t('Umbrella: Contacts');

if (post('EMAIL')){
	$vcard = new VCard($_POST);
	$vcard->save();
	redirect('index');
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Create new contact') ?></legend>
		<fieldset>
			<legend><?= t('Name'); ?></legend>
			<label><?= t('First Name') ?>
				<input type="text" name="N[2]" <?= ($name = post('N'))?'value="'.$name['2'].'"':''?>/>
			</label>
			<label><?= t('Last Name') ?>
				<input type="text" name="N[1]" <?= ($name = post('N'))?'value="'.$name['1'].'"':''?> />
			</label>
		</fieldset>
		<fieldset>
			<legend><?= t('(primary) Email') ?></legend>
			<input type="text" name="EMAIL"<?= ($email = post('EMAIL'))?'value="'.$email.'"':''?>  />
		</fieldset>
		<fieldset>
			<legend><?= t('Organization') ?></legend>
			<textarea name="ORG"><?= ($org = post('ORG'))?$org:'' ?></textarea>
		</fieldset>
		<fieldset>
			<legend><?= t('(primary) Address') ?></legend>
			<label class="street"><?= t('Street') ?>
				<input type="text" name="ADR[3]" <?= ($adr = post('ADR'))?'value="'.$adr['3'].'"':''?> />
			</label>
			<label><?= t('Post Code') ?>
				<input type="text" name="ADR[6]" <?= ($adr = post('ADR'))?'value="'.$adr['6'].'"':''?>/>
			</label>
			<label class="location"><?= t('Location') ?>
				<input type="text" name="ADR[4]" <?= ($adr = post('ADR'))?'value="'.$adr['4'].'"':''?>/>
			</label>
			<label class="region"><?= t('Region') ?>
				<input type="text" name="ADR[5]" <?= ($adr = post('ADR'))?'value="'.$adr['5'].'"':''?>/>
			</label>
			<label><?= t('Country') ?>
				<input type="text" name="ADR[7]" <?= ($adr = post('ADR'))?'value="'.$adr['7'].'"':''?>/>
			</label>
		</fieldset>
		<button type="submit"><?= t('Save') ?></button>
	</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
