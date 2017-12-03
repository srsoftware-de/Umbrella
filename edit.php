<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');

$id = param('id');
assert(is_numeric($id),'No valid invoice id passed to edit!');
$invoice = reset(Invoice::load($id));

assert($invoice !== null,'No invoice found or accessible for id = '.$id);



if ($services['time']){
	$times = request('time', 'json_list');
	$tasks = array();
	foreach ($times as $time_id => $time){
		if ($time['end_time']===null) {
			unset($times[$time_id]);
			continue;
		}
		foreach ($time['tasks'] as $task_id => $dummy) $tasks[$task_id]=null;
	}
	
	$tasks = request('task', 'json',['ids'=>implode(',', array_keys($tasks))]);
	// add times selected by user to invoice
	if ($selected_times = post('times')){
		$customer_price = 50*100; // TODO: get customer price
		$timetrack_tax = 19.0; // TODO: make adjustable
		foreach ($selected_times as $time_id => $dummy){
			$time = $times[$time_id];
			$duration = ($time['end_time']-$time['start_time'])/3600;
			$description = $time['description'];
			if ($description === null || trim($description) == ''){
				$description = '';
				foreach ($time['tasks'] as $tid => $dummy){
					$description .= '- '.$tasks[$tid]['name']."\n";
				}
			}
			$position = new InvoicePosition($invoice);
			$position->patch([
					'item_code'=>t('timetrack'),
					'amount'=>$duration,
					'unit'=>t('hours'),
					'title'=>$time['subject'],
					'description'=>$description,
					'single_price'=>$customer_price,
					'tax'=>$timetrack_tax]);
			$position->save();
		}
	}
	
	if ($position_data = post('position')){
		$positions = $invoice->positions();
		foreach ($position_data as $pos => $data){
			$data['single_price'] *= 100;// * $data['single_price'];
			$positions[$pos]->patch($data);
			$positions[$pos]->save();
		}		
	}
		
	$projects = array();
	foreach ($tasks as $task_id => $task) $projects[$task['project_id']] = null;

	$projects = request('project', 'json',['ids'=>implode(',', array_keys($projects))]);

	foreach ($times as $time_id => &$time){
		foreach ($time['tasks'] as $task_id => $task){
			$project_id = $tasks[$task_id]['project_id'];
			$project = &$projects[$project_id];
			if (!isset($project['times'])) $project['times'] = array();
			
			$project['times'][$time_id] = $time;
		}
		
	}
}

if (isset($_POST['invoice'])){
	$new_invoice_data = $_POST['invoice'];
	$new_invoice_data['date'] = strtotime($new_invoice_data['date']);
	$invoice->patch($new_invoice_data);
	$invoice->save();

	$companySettings = CompanySettings::load($invoice->company_id);
	$companySettings->updateFrom($invoice);
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>



<form method="POST" class="invoice">
	<fieldset>
		<legend><?= t('Edit invoice')?></legend>
		<fieldset class="customer">
			<legend><?= t('Customer')?></legend>
			<textarea name="invoice[customer]"><?= $invoice->customer ?></textarea>
			<fieldset>
				<legend><?= t('Customer number')?></legend>
				<input name="invoice[customer_number]" value="<?= $invoice->customer_number ?>" />
			</fieldset>		
			
		</fieldset>
		<fieldset class="sender">
			<legend><?= t('Sender')?></legend>
			<textarea name="invoice[sender]"><?= $invoice->sender ?></textarea>			
			<fieldset>
				<legend><?= t('Tax number')?></legend>
				<input name="invoice[tax_number]" value="<?= $invoice->tax_number ?>" />
			</fieldset>		
		</fieldset>
		
		<fieldset class="dates">
			<legend><?= t('Dates')?></legend>
			<label><?= t('Invoice Date')?>
				<input name="invoice[date]" value="<?= $invoice->date() ?>" />
			</label>
			<label><?= t('Delivery Date')?>
				<input name="invoice[delivery_date]" value="<?= $invoice->delivery_date() ?>" />
			</label>
			<label><?= t('Invoice number')?>
				<input name="invoice[number]" value="<?= $invoice->number ?>" />
			</label>
						
		</fieldset>
		
		<fieldset class="header">
			<legend>
				<?= t('Greeter/Head text')?>
			</legend>
			<textarea name="invoice[head]"><?= $invoice->head ?></textarea>
		</fieldset>
		<fieldset class="invoice_positions">
			<legend><?= t('Positions')?></legend>
			<table>
				<tr>
					<th><?= t('Pos')?></th>
					<th><?= t('Code')?></th>
					<th>
						<span class="title"><?= t('Title')?></span>/
						<span class="description"><?= t('Description')?></span>
					</th>
					<th><?= t('Amount')?></th>
					<th><?= t('Unit')?></th>
					<th><?= t('Price')?></th>
					<th><?= t('Price')?></th>
					<th><?= t('Actions')?></th>
				</tr>

				<?php $first = true; 
					foreach ($invoice->positions() as $pos => $position) { ?>
				<tr>
					<td><?= $pos ?></td>
					<td><input name="position[<?= $pos ?>][item_code]" value="<?= $position->item_code ?>" /></td>
					<td>
						<input name="position[<?= $pos?>][title]" value="<?= $position->title ?>" />
						<textarea name="position[<?= $pos?>][description]"><?= $position->description ?></textarea>
					</td>
					<td><input name="position[<?= $pos?>][amount]" value="<?= $position->amount ?>" /></td>
					<td><?= t($position->unit)?></td>
					<td><input class="price" name="position[<?= $pos?>][single_price]" value="<?= $position->single_price/100?>" /></td>
					<td><?= round($position->single_price*$position->amount/100,2) ?></td>
					<td>
					<?php if (!$first) { ?>
						<a class="symbol" title="<?= t('move up')?>" href="elevate?pos=<?= $pos ?>"></a>
					<?php }?>
					</td>
				</tr>				
				<?php $first = false; }?>
			</table>
		</fieldset>

		<fieldset class="add_positions">
			<legend><?= t('Add Positions')?></legend>
			<ul>			
			<?php if ($projects) foreach ($projects as $project_id => $project) {?>
				<li>
					<?= $project['name']?>
					<ul>
					<?php foreach ($project['times'] as $time_id => $time) { ?>
						<li>
							<label>
							<input type="checkbox" name="times[<?= $time_id?>]" />							
							<span class="subject"><?= $time['subject']?></span>
							<span class="description"><?= $time['description']?></span>
							<span class="duration">(<?= round(($time['end_time']-$time['start_time'])/3600,2)?>&nbsp;<?= t('hours')?>)</span>
							<ul>
							<?php foreach ($time['tasks'] as $task_id => $task) { ?>
								<li><?= $tasks[$task_id]['name']?></li>
							<?php } ?>
							</ul>
							</label>
						</li>
					<?php }?>
					</ul>
				</li>
			<?php }?>
			</ul>
		</fieldset>
		<fieldset>
			<legend>
				<?= t('Footer text')?>
			</legend>
			<textarea name="invoice[footer]"><?= $invoice->footer ?></textarea>
		</fieldset>
		<fieldset class="court">
			<legend>
				<?= t('Bank account')?>
			</legend>
			<textarea name="invoice[bank_account]"><?= $invoice->bank_account ?></textarea>
		</fieldset>
		<fieldset>
			<legend>
				<?= t('Local court')?>
			</legend>
			<input type="text" name="invoice[court]" value="<?= $invoice->court ?>"/>
		</fieldset>
		
		<button type="submit"><?= t('Save')?></button>		
		<a class="button" title="<?= t('Download PDF') ?>" href="pdf"><?= t('Donwload PDF') ?></a>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
