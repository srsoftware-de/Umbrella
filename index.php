<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');
$invoices = Invoice::load();
//debug($invoices);
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table class="invoices">
	<tr>
		<th><?= t('Sender')?></th>
		<th><?= t('Customer')?></th>
		<th><?= t('Actions')?></th>
	</tr>
	<?php foreach ($invoices as $id => $invoice){ ?>
	<tr>
		<td><pre><?= $invoice['sender']?></pre></td>
		<td><pre><?= $invoice['customer']?></pre></td>
		<td>
			<a title="<?= t('edit')?>"     href="<?= $id ?>/edit?redirect=../index"     class="symbol"></a>
		</td>
	</tr>
	<?php } ?>
</table>

<?php include '../common_templates/closure.php'; ?>