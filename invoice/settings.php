<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login();

$settings = get_settings($user);
if (!$settings){
	$settings = array('decimal_separator'=>'.',
					'thousands_separator'=>',',
					'currency'=>'€',
					'decimals'=>2,
					'default_invoice_header'=>t('default_invoice_header_text'),
					'default_invoice_footer'=>t('default_invoice_footer_text'),
					'invoice_prefix'=>'',
					'invoice_number'=>1,
					'invoice_suffix'=>'',
			);
}

if (isset($_POST['decimals'])) {
	foreach ($settings as $key => $val){
		if (isset($_POST[$key])) $settings[$key]=$_POST[$key];
	}
	save_settings($settings);
} 
unset($settings['user_id']);

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<h2><?= t('Your invoice settings')?></h2>
<form method="POST" class="invoice">
	<?php foreach ($settings as $key => $value){ ?>
		<fieldset>
			<legend><?= t($key)?></legend>
			<?php if (strlen($value) > 20) { ?>
			<textarea name="<?= $key?>"><?= $value ?></textarea>
			<?php } else { ?>
			<input name="<?= $key ?>" type="text" value="<?= $value ?>" />
			<?php } ?>
		</fieldset>
	<?php }?>
	<input type="submit" />
</form>

<?php include '../common_templates/closure.php'; ?>
