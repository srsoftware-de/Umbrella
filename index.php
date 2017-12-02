<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');
$invoices = Invoice::load();

$companies = request('company','json_list');

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>



<?php foreach ($companies as $company){ ?>
<fieldset>
	<legend><?= $company['name']?></legend>
	<a href="add?company=<?= $company['id']?>">add invoice</a>
	<table class="invoices">
		<tr>
			<th><?= t('Number')?></th>
			<th><?= t('Date')?></th>
			<th><?= t('State')?></th>
			<th><?= t('Customer')?></th>
			<th><?= t('Actions')?></th>
		</tr>
		<?php foreach ($invoices as $id => $invoice){
			if ($invoice->company_id != $company['id']) continue; ?>
		<tr>
			<td><?= $invoice->number ?></td>
			<td><?= $invoice->date() ?></td>
			<td><?= $invoice->state()?></td>
			<td><?= $invoice->customer_short()?></td>
			<td></td>
		</tr>
		<?php } ?>
		
	</table>
</fieldset>
<?php }

include '../common_templates/closure.php'; ?>