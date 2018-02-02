<?php $title = 'Umbrella Document Management';

include '../bootstrap.php';
include 'controller.php';

require_login('document');

if ($template_data = param('template')){
	$template = new Template($template_data['file']);
	$template->patch($template_data);
	$template->save();
	redirect('index');
}

$companies = request('company','json');

if ($filename = param('file')){
	$extension = '.HTML';
	if (substr(strtoupper($filename),-strlen($extension)) != $extension) warn('File is not an ? file!',$extension);
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend><?= t('Add template')?></legend>
	<select name="template[company_id]">
		<option value=""><?= t('Assign template to company')?></option>
		<?php foreach ($companies as $company){?>
		<option value="<?= $company['id'] ?>"><?= $company['name']?></option>
		<?php }?>
	</select>
	<input type="text" name="template[name]" value="<?= t('Template ?',date('Y-m-d H:i:s'));?>" />
	<input type="text" name="template[file]" readonly="true" value="<?= param('file') ?>" />
	<input type="submit" />	
</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
