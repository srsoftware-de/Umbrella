<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';

require_login('tag');
$tags = get_tag_list();
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend>Tags</legend>
<?php foreach ($tags as $tag => $data){ ?>
	<a class="button" href="<?= getUrl('tag',urlencode($tag).'/view') ?>"><?= $tag ?></a>
<?php } ?>
</fieldset>

<?php include '../common_templates/closure.php'; ?> 