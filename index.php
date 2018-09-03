<?php include 'controller.php';

require_login('rtc');

$channels = Channel::load();
$base_url = getUrl('rtc');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php'; 
include '../common_templates/messages.php'; ?>

<table>
	<tr>
		<th><?= t('Channel') ?></th>
		<th><?= t('Users') ?></th>
		<th><?= t('Actions') ?></th>
	</tr>
	<?php foreach ($channels as $id => $channel){ ?>
	<tr>
		<td><a target="_blank" href="<?= $base_url.$id.'/open' ?>"><?= $channel->subject ?></a></td>
		<td>
		<?php foreach ($channel->users() as $u){ ?> 
			<span><?= $u['login']?></span>	
		<?php }?>
		</td>
		<td>
			<a class="symbol" title="<?= t('add user') ?>" href="<?= $id ?>/add_user"> </a>
		</td>
	</tr>
	<?php } ?>
</table>

<?php include '../common_templates/closure.php'; ?>