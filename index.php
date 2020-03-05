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
	<table>
		<tr>
			<th><?= t('Name')?>&nbsp;/ <?= t('Participate') ?></th>
			<th><?= t('Description')?></th>
			<th><?= t('Actions')?></th>
		</tr>
		<?php foreach ($polls as $id => $poll) {
			if (empty($poll->users($user->id))) continue;
			$base_url = getUrl('poll',$id);
		?>
		<tr>
			<td><a target="_blank" href="<?= $base_url.'/view'?>"><?= $poll->name ?></a></td>
			<td><?= markdown($poll->description) ?></td>
			<td>
				<a class="button" href="<?= $base_url.'/edit'?>"><?= t('edit') ?></a>
				<a class="button" href="<?= $base_url.'/options' ?>"><?= t('Edit options') ?></a>
				<a class="button" href="<?= $base_url.'/share' ?>"><?= t('Share') ?></a>
				<a class="button" href="<?= $base_url.'/evaluate' ?>"><?= t('Evaluation') ?></a>
			</td>
		</tr>
		<?php } // for each poll ?>
	</table>
</fieldset>

<?php include '../common_templates/closure.php';