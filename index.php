<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');
$invoices = Invoice::load();
$companies = request('company','json');

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<br/>
<?php foreach ($companies as $cid => $company){ ?>
<fieldset class="invoice list">
	<legend><?= $company['name']?></legend>
	<a href="add?company=<?= $cid?>"><?= t('add document') ?></a>
	<table class="invoices">
		<tr>
			<th><?= t('Number')?></th>
			<th><?= t('Sum')?></th>
			<th><?= t('Date')?></th>
			<th><?= t('State')?></th>
			<th><?= t('Customer')?></th>
			<th><?= t('Actions')?></th>
		</tr>
		<?php foreach ($invoices as $id => $invoice){
			if ($invoice->company_id != $cid) continue; ?>
		<tr>
			<td><a href="<?= $invoice->id ?>/view"><?= $invoice->number ?></a></td>
			<td><a href="<?= $invoice->id ?>/view"><?= $invoice->sum().' '.$invoice->currency ?></a></td>
			<td><a href="<?= $invoice->id ?>/view"><?= $invoice->date() ?></a></td>
			<td><a href="<?= $invoice->id ?>/view"><?= t($invoice->state()) ?></a></td>
			<td><a href="<?= $invoice->id ?>/view"><?= $invoice->customer_short()?></a></td>
			<td><a href="<?= $invoice->id ?>/step"><?= t([Invoice::TYPE_OFFER=>'create confirmation',Invoice::TYPE_CONFIRMATION=>'create invoice',Invoice::TYPE_INVOICE=>'create reminder',Invoice::TYPE_REMINDER=>'add reminder'][$invoice->type])?></a></td>
			</td>
		</tr>
		<?php } ?>
		
	</table>
</fieldset>
<?php }

include '../common_templates/closure.php'; ?>
