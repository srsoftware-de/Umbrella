<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login();

$id = param('id');
assert(is_numeric($id),'No valid invoice id passed to edit!');
$invoice = list_invoices($id);
assert(isset($invoice[$id]),'No invoice found or accessible for id = '.$id);
$invoice = $invoice[$id];

if ($customer = post('customer')){
	$keys = ['customer','customer_num','sender','tax_num','invoice_date','delivery_date','head','footer'];
	foreach ($keys as $key) {
		if ($value = post($key)) $invoice[$key] = $value;
	}
	save_invoice($id,$invoice);
}


function conclude_vcard($vcard){
	$short = '';
	if (isset($vcard['N'])){
		$names = explode(';',$vcard['N']);
		$short = $names[2].' '.$names[1];
	}
	if (isset($vcard['ORG'])){		
		$org = str_replace(';', ', ', $vcard['ORG']);
		if ($short != '') $short.=', ';
		$short .= $org;		
	}
	debug($short);
}

$head_text = post('head','Wir erlauben uns, Ihnen die folgenden Positionen in Rechnung zu stellen:');
$foot_text = post('foot',"Zahlbar innerhalb von 14 Tagen ohne Abzug.\n\nUnberechtigt abgezogene Skontobeträge werden nachgefordert.\nLieferung frei Haus.\nGeben Sie bei Rückfragen und bei Überweisung bitte ihre Kundennummer und Rechnungsnummern an!\n\n Wir danken für Ihren Auftrag.");
$tax_number = post('tax_number','XXX');
$customer_number = post('customer_number','XXX');
$sender = post('sender','XXX');
$projects = null;
if ($services['time']){
	$times = request('time', 'json_list');
	
	if ($selected_times = post('times')){
		$customer_price = 50*100; // TODO: get customer price
		$timetrack_tax = 19.0; // TODO: make adjustable
		foreach ($selected_times as $time_id => $dummy){
			$time = $times[$time_id];
			$duration = ($time['end_time']-$time['start_time'])/3600;
			
			add_invoice_position($id,'timetrack',$time['subject'],$time['description'],$duration,'hours',$customer_price,$timetrack_tax);
		}		
	}
	
	
	$tasks = array();
	foreach ($times as $time_id => $time){
		foreach ($time['tasks'] as $task_id => $dummy) $tasks[$task_id]=null;
	}		
	$tasks = request('task', 'json?ids='.implode(',', array_keys($tasks)));
	
	
	$projects = array();
	foreach ($tasks as $task_id => $task) $projects[$task['project_id']] = null;	
	$projects = request('project', 'json?ids='.implode(',', array_keys($projects)));
	
	
	foreach ($times as $time_id => &$time){
		foreach ($time['tasks'] as $task_id => $task){
			$task = &$tasks[$task_id];			
			$project_id = $task['project_id'];
			$project = &$projects[$project_id];
			if (!isset($project['times'])) $project['times'] = array();
			
			$time['tasks'][$task_id] = $task;
			
			$project['times'][$time_id] = $time;
			
										
		} 
	}
	//debug($projects);
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST" class="invoice">
	<fieldset>
		<legend>Edit invoice</legend>
		<fieldset class="customer">
			<legend>Customer</legend>
			<textarea name="customer"><?= $invoice['customer'] ?></textarea>
			<fieldset>
				<legend>Customer number</legend>
				<input name="customer_num" value="<?= $invoice['customer_num'] ?>" />
			</fieldset>		
			
		</fieldset>
		<fieldset class="sender">
			<legend>Sender</legend>
			<textarea name="sender"><?= $invoice['sender'] ?></textarea>			
			<fieldset>
				<legend>Tax number</legend>
				<input name="tax_number" value="<?= $invoice['tax_num'] ?>" />
			</fieldset>		
		</fieldset>
		
		<fieldset>
			<legend>Dates</legend>
			<label>Invoice Date
				<input name="invoice_date" value="<?= ($invoice_date = post('invoice_date'))?$invoice_date:date('Y-m-d')?>" />
			</label>
			<label>Delivery Date
				<input name="delivery_date" value="<?= ($delivery_date = post('delivery_date'))?$delivery_date:date('Y-m-d')?>" />
			</label>			
		</fieldset>
		<fieldset>
			<legend>
				Greeter/Head text
			</legend>
			<textarea name="head"><?= $head_text ?></textarea>
		</fieldset>
		<fieldset>
			<legend>Add Positions</legend>
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
							<span class="duration">(<?= ($time['end_time']-$time['start_time'])/3600?>&nbsp;hours)</span>
							<ul>
							<?php foreach ($time['tasks'] as $task_id => $task) { ?>
								<li><?= $task['name']?></li>
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
				Footer text
			</legend>
			<textarea name="footer"><?= $foot_text ?></textarea>
		</fieldset>
		<button type="submit">Save</button>		
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
