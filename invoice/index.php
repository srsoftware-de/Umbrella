<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login();
$invoices = list_invoices();
//debug($invoices);
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table class="invoices">
	<tr>
		<th>Sender</th>
		<th>Customer</th>
		<th>Actions</th>
	</tr>
	<?php foreach ($invoices as $id => $invoice){ ?>
	<tr>
		<td><pre><?= $invoice['sender']?></pre></td>
		<td><pre><?= $invoice['customer']?></pre></td>
		<td><a href="<?= $id?>/edit">Edit</a></td>
	</tr>
	<?php } ?>
</table>

<?php include '../common_templates/closure.php'; ?>