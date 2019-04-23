<?php include 'controller.php';

require_login('poll');

if ($key = param('key')){
	$polls = Poll::load(['key'=>$key]);
	if (!empty($polls)){ ?>
		<table class="polls">
			<tr>
				<th><?= t('name') ?></th>
				<th><?= t('description') ?></th>
			</tr>
		<?php foreach ($polls as $id => $poll) { ?>
			<tr>
				<td><a href="<?= getUrl('poll','view?id='.$id) ?>"><?= emphasize($poll->name,$key) ?></a></td>
				<td class="poll"><?= emphasize(markdown($poll->description),$key) ?></td>
			</tr>
		<?php } ?>
		</table>
	<?php }
}