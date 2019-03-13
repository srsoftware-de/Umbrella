<?php include 'controller.php';

require_login('poll');

$polls = Poll::load();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Your polls')?></legend>
	<ul>
		<?php foreach ($polls as $id => $poll) { ?>
		<li>
			<a class="button" href="<?= getUrl('poll','options?id='.$id) ?>"><?= $poll->name ?></a>
			<a class="button" href="<?= getUrl('poll','evaluate?id='.$id) ?>"><?= t('Evaluation') ?></a>
			<a target="_blank" href="<?= getUrl('poll','view?id='.$id)?>"><?= getUrl('poll','view?id='.$id)?></a>
		</li>
		<?php } ?>
	</ul>
</fieldset>

<?php include '../common_templates/closure.php';