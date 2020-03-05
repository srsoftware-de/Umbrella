<?php include 'controller.php';

require_login('poll');

$polls = Poll::load();


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Your polls')?>
		<span class="symbol">
			<a href="<?= getUrl('poll','add') ?>"></a>
		</span>
	</legend>
	<ul>
		<?php foreach ($polls as $id => $poll) {
			$base_url = getUrl('poll',$id);
		?>
		<li>
			<a target="_blank" href="<?= $base_url.'/view'?>"><?= $poll->name ?></a>
			<a class="button" href="<?= $base_url.'/edit'?>"><?= t('edit') ?></a>
			<a class="button" href="<?= $base_url.'/options' ?>"><?= t('Edit options') ?></a>
			<a class="button" href="<?= $base_url.'/evaluate' ?>"><?= t('Evaluation') ?></a>

		</li>
		<?php } ?>
	</ul>
</fieldset>

<?php include '../common_templates/closure.php';