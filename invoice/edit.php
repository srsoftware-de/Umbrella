<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login();

$id = param('id');
assert(is_numeric($id),'No valid invoice id passed to edit!');
$invoice = load_invoices($id);
assert($invoice !== null,'No invoice found or accessible for id = '.$id);

if ($customer = post('customer')){
	$keys = ['customer','customer_num','sender','tax_num','invoice_date','delivery_date','head','footer'];
	foreach ($keys as $key) {
		if ($value = post($key)) $invoice[$key] = $value;
	}
	save_invoice($id,$invoice);
}

$head_text = post('head','Wir erlauben uns, Ihnen die folgenden Positionen in Rechnung zu stellen:');
$foot_text = post('foot',"Zahlbar innerhalb von 14 Tagen ohne Abzug.\n\nUnberechtigt abgezogene Skontobeträge werden nachgefordert.\nLieferung frei Haus.\nGeben Sie bei Rückfragen und bei Überweisung bitte ihre Kundennummer und Rechnungsnummern an!\n\n Wir danken für Ihren Auftrag.");
$tax_number = post('tax_number','XXX');
$customer_number = post('customer_number','XXX');
$sender = post('sender','XXX');
$projects = null;
if ($services['time']){
	$times = request('time', 'json_list');
	
	$tasks = array();
	foreach ($times as $time_id => $time){
		foreach ($time['tasks'] as $task_id => $dummy) $tasks[$task_id]=null;
	}
	$tasks = request('task', 'json?ids='.implode(',', array_keys($tasks)));
	
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
			add_invoice_position($id,'timetrack',$time['subject'],$description,$duration,'hours',$customer_price,$timetrack_tax);
		}
	}
	
	
		
	$projects = array();
	foreach ($tasks as $task_id => $task) $projects[$task['project_id']] = null;

	$projects = request('project', 'json?ids='.implode(',', array_keys($projects)));

	foreach ($times as $time_id => &$time){
		foreach ($time['tasks'] as $task_id => $task){
			$project_id = $tasks[$task_id]['project_id'];
			$project = &$projects[$project_id];
			if (!isset($project['times'])) $project['times'] = array();
			
			$project['times'][$time_id] = $time;
		}
		
	}
}

load_positions($invoice);

if ($positions = post('position')){
	$keys = array('item_code','title','description','amount','single_price');
	
	foreach ($positions as $pos => $position){
		foreach ($keys as $key){
			if ($key == 'single_price') $position[$key] = $position[$key]*100;
			if ($invoice['positions'][$pos][$key] != $position[$key]){
				$changed[$pos] = true;
				$invoice['positions'][$pos][$key] = $position[$key];
			}
		}	
	}
	foreach ($changed as $pos => $dummy){
		save_invoice_position($invoice['positions'][$pos]);
	}
	//if ($redirect = param('redirect')) redirect($redirect);
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
			<textarea name="customer"><?= $invoice['customer'] ?></textarea>
			<fieldset>
				<legend><?= t('Customer number')?></legend>
				<input name="customer_num" value="<?= $invoice['customer_num'] ?>" />
			</fieldset>		
			
		</fieldset>
		<fieldset class="sender">
			<legend><?= t('Sender')?></legend>
			<textarea name="sender"><?= $invoice['sender'] ?></textarea>			
			<fieldset>
				<legend><?= t('Tax number')?></legend>
				<input name="tax_number" value="<?= $invoice['tax_num'] ?>" />
			</fieldset>		
		</fieldset>
		
		<fieldset>
			<legend><?= t('Dates')?></legend>
			<label><?= t('Invoice Date')?>
				<input name="invoice_date" value="<?= ($invoice_date = post('invoice_date'))?$invoice_date:date('Y-m-d')?>" />
			</label>
			<label><?= t('Delivery Date')?>
				<input name="delivery_date" value="<?= ($delivery_date = post('delivery_date'))?$delivery_date:date('Y-m-d')?>" />
			</label>			
		</fieldset>
		<fieldset>
			<legend>
				<?= t('Greeter/Head text')?>
			</legend>
			<textarea name="head"><?= $head_text ?></textarea>
		</fieldset>
		<fieldset>
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
					foreach ($invoice['positions'] as $pos => $position) { ?>
				<tr>
					<td><?= $position['pos']?></td>
					<td><input name="position[<?= $pos ?>][item_code]" value="<?= $position['item_code']?>" /></td>
					<td>
						<input name="position[<?= $pos?>][title]" value="<?= $position['title']?>" />
						<textarea name="position[<?= $pos?>][description]"><?= $position['description']?></textarea>
					</td>
					<td><input name="position[<?= $pos?>][amount]" value="<?= $position['amount']?>" /></td>
					<td><?= t($position['unit'])?></td>
					<td><input class="price" name="position[<?= $pos?>][single_price]" value="<?= $position['single_price']/100?>" /></td>
					<td><?= round($position['single_price']*$position['amount']/100,2) ?></td>
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
			<textarea name="footer"><?= $foot_text ?></textarea>
		</fieldset>
		<button type="submit"><?= t('Save')?></button>		
		<a class="button" title="<?= t('Download PDF') ?>" href="pdf"><?= t('Donwload PDF') ?></a>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
