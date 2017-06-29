<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login();
$invoices = list_invoices($user->id);

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table class="invoices">
	<tr>
		<th>ID</th>
		<th>mops</th>
	</tr>
	<?php foreach ($invoices as $id => $invoice){ ?>
	<tr>
		<th><?= $id ?></th>
		<th>mops</th>
	</tr>
	<?php } ?>
</table>

<?php include '../common_templates/closure.php'; ?>