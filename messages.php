<?php include 'controller.php';

$user = User::require_login();

$limit = param('limit');
if (empty($limit)){
	$messages = Message::load(['since'=>$user->last_logoff]);
} else $messages = Message::load(['limit'=>$limit]);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<fieldset>
	<legend>
		<?= empty($limit)?t('Messages since last logoff'):t('Last ◊ messages',$limit)?>
	</legend>
	<table class="messages">
		<tr>
			<th><?= t('Created')?></th>
			<th><?= t('From')?></th>
			<th><?= t('Subject')?></th>
			<th><?= t('Text')?></th>
			<th><?= t('State')?></th>
		</tr>
		<?php foreach ($messages as $message){ ?>
		<tr>
			<td><?= date('Y-m-d / H:i:s',$message->timestamp) ?></td>
			<td><?= $message->from->login ?></td>
			<td><?= $message->subject ?></td>
			<td><?= $message->body ?></td>
			<td>
				<?php switch ($message->state){
					case Message::SENT: echo t('Already sent via mail.'); break;
					case Message::WAITING: echo t('Waiting to be sent by mail.'); break;
					default: echo t('Unknown message state');
				}
				?>
			</td>
		</tr>
		<?php } ?>
	</table>
</fieldset>
<?php
include '../common_templates/closure.php';