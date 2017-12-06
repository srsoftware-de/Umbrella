<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');

$templates = Template::load(param('company'));

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add template')?></legend>
	<a href="<?= getUrl('files','select?target='.getUrl('invoice','add_template')) ?>"><?= t('Select a file') ?></a>
</fieldset>
<?php if (!empty($templates)) { ?>
<fieldset>
	<legend><?= t('Existing templates') ?></legend>
	<ul>
	<?php foreach ($templates as $template){ ?>
		<li><?= $template->name ?></li>
	<?php } ?>
	</ul>
<?php } // not empty ?>
</fieldset>

<?php include '../common_templates/closure.php'; ?>