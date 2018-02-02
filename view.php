<?php

include '../bootstrap.php';
include 'controller.php';

$title = t('Umbrella: Document Management');
require_login('document');

$id = param('id');
assert(is_numeric($id),'No valid document id passed to edit!');
$document = reset(Document::load(['ids'=>$id]));
if (!$document) error('No document found or accessible for id ?',$id);

if ($services['time']){
	if (isset($document->company_id) && $document->company_id !== null){
		$projects = request('project','json',['company_ids'=>$document->company_id]); // get all projects of the documents' company
		$tasks = request('task','json',['project_ids'=>array_keys($projects)]); // get all tasks of the projects
		$times = request('time','json',['task_ids'=>array_keys($tasks)]); // get all times for tasks of the project
		$user_ids = [];
		foreach ($times as $time_id => $time){
			$user_ids[$time['user_id']] = true;
			foreach ($time['task_ids'] as $task_id){
				if (!isset($tasks[$task_id])) continue;
				$task = $tasks[$task_id];
				$time['tasks'][$task_id] = $task;
				$project_id = $task['project_id'];
				$projects[$project_id]['times'][$time_id] = $time;
			}
		}

		$users = request('user','json',['ids'=>array_keys($user_ids)]);

		// add times selected by user to document
		if ($selected_times = post('times')){
			$customer_price = 50*100; // TODO: get customer price
			$timetrack_tax = 19.0; // TODO: make adjustable
			foreach ($selected_times as $time_id => $dummy){

				$time = $times[$time_id];
				$duration = round(($time['end_time']-$time['start_time'])/3600,2); // TODO: make decimals adjustable
				$description = $time['description'];
				if ($description === null || trim($description) == ''){
					$description = '';
					foreach ($time['tasks'] as $tid => $dummy){
						$description .= '- '.$tasks[$tid]['name']."\n";
					}
				}
				$position = new DocumentPosition($document);
				$position->patch([
						'item_code'=>t('timetrack'),
						'amount'=>$duration,
						'unit'=>t('hours'),
						'title'=>$time['subject'],
						'description'=>$description,
						'single_price'=>$customer_price,
						'tax'=>$timetrack_tax,
						'time_id'=>$time_id]);
				$position->save();
			}
			request('time','update_state',['PENDING'=>implode(',',array_keys($selected_times))]);
		}
	}
}

if (isset($services['items'])){
	$items = request('items','json',['company'=>$document->company_id]);
	if ($selected_items = post('items')){
		foreach ($selected_items as $item_id => $dummy){
			$item = $items[$item_id];
			$position = new DocumentPosition($document);
			$position->patch([
					'item_code'=>$item['code'],
					'amount'=>1,
					'unit'=>$item['unit'],
					'title'=>$item['name'],
					'description'=>$item['description'],
					'single_price'=>$item['unit_price'],
					'tax'=>$item['tax']]);
			$position->save();
		}
	}
}


if ($position_data = post('position')){
	$positions = $document->positions();
	foreach ($position_data as $pos => $data){
		$data['single_price'] *= 100;// * $data['single_price'];
		$positions[$pos]->patch($data);
		$positions[$pos]->save();
	}
}

if (isset($_POST['document'])){
	$new_document_data = $_POST['document'];
	$new_document_data['date'] = strtotime($new_document_data['date']);
	$document->patch($new_document_data);
	$document->save();
	info('Your document ? has been saved.',$document->number);

	$companySettings = CompanySettings::load($document->company_id,$document->type_id);
	$companySettings->updateFrom($document);
	info('Company settings have been updated.');
}

$templates = Template::load($document->company_id);
if (empty($templates)) warn('No templates have been provided for this company!');

if (isset($services['bookmark'])){
	$hash = sha1(getUrl('document',$id.'/view'));
	$bookmark = request('bookmark','json_get?id='.$hash);
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

$texts1 = [1=>'offer date',2=>'confirmation date',3=>'document date',4=>'reminder date'];
$texts2 = [1=>'offer number',2=>'confirmation number',3=>'document number',4=>'reminder number'];

?>



<form method="POST" class="document">
	<fieldset>
		<legend><?= t('Edit document')?></legend>
		<fieldset class="customer">
			<legend><?= t('Customer')?></legend>
			<textarea name="document[customer]"><?= $document->customer ?></textarea>
			<fieldset>
				<legend><?= t('Customer number')?></legend>
				<input name="document[customer_number]" value="<?= $document->customer_number ?>" />
			</fieldset>
			<fieldset>
				<legend><?= t('Customer tax number')?></legend>
				<input name="document[customer_tax_number]" value="<?= $document->customer_tax_number ?>" />
			</fieldset>
			<fieldset>
				<legend><?= t('Customer email')?></legend>
				<input name="document[customer_email]" value="<?= $document->customer_email ?>" />
			</fieldset>

		</fieldset>
		<fieldset class="sender">
			<legend><?= t('Sender')?></legend>
			<textarea name="document[sender]"><?= $document->sender ?></textarea>
			<fieldset>
				<legend><?= t('Tax number')?></legend>
				<input name="document[tax_number]" value="<?= $document->tax_number ?>" />
			</fieldset>
		</fieldset>

		<fieldset class="dates">
			<legend><?= t('Dates')?></legend>
			<label><?= t($texts1[$document->type_id])?>
				<input name="document[date]" value="<?= $document->date() ?>" />
			</label>
			<label><?= t('Delivery Date')?>
				<input name="document[delivery_date]" value="<?= $document->delivery_date() ?>" />
			</label>
			<label><?= t($texts2[$document->type_id])?>
				<input name="document[number]" value="<?= $document->number ?>" />
			</label>
			<label><?= t('State'); ?>
				<select name="document[state]">
				<?php foreach (Document::states() as $state => $text){ ?>
					<option value="<?= $state ?>" <?= $document->state == $state ? 'selected="true"' :''?> ><?= t($text) ?></option>
				<?php } ?>
				</select>
			<label>
		</fieldset>

		<fieldset class="header">
			<legend>
				<?= t('Greeter/Head text')?>
			</legend>
			<textarea name="document[head]"><?= $document->head ?></textarea>
		</fieldset>
		<fieldset class="document_positions">
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
					<th><?= t('Tax')?></th>
					<th><?= t('Actions')?></th>
				</tr>

				<?php $first = true;
					foreach ($document->positions() as $pos => $position) { ?>
				<tr>
					<td><?= $pos ?></td>
					<td>
						<input name="position[<?= $pos ?>][item_code]" value="<?= $position->item_code ?>" />
						<?php if ($position->time_id !== null) {?><br/>
						<a href="<?= getUrl('time',$position->time_id.'/view') ?>"><?= t('Show time')?></a>
						<?php } ?>
					</td>
					<td>
						<input name="position[<?= $pos?>][title]" value="<?= $position->title ?>" />
						<textarea name="position[<?= $pos?>][description]"><?= $position->description ?></textarea>
					</td>
					<td><input class="amount" name="position[<?= $pos?>][amount]" value="<?= $position->amount ?>" /></td>
					<td><?= t($position->unit)?></td>
					<td><input class="price" name="position[<?= $pos?>][single_price]" value="<?= $position->single_price/100?>" /></td>
					<td class="pos_price"><?= round($position->single_price*$position->amount/100,2) ?></td>
					<td><div class="tax"><input name="position[<?= $pos?>][tax]" value="<?= $position->tax?>" /> %</div></td>
					<td>
						<a class="symbol" title="<?= t('drop')?>" href="drop?pos=<?= $pos ?>"></a>
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
			<?php if ($projects) { ?>
				<li>
					<?= t('Timetrack')?>
					<ul>
						<?php foreach ($projects as $project_id => $project) {
							if (!isset($project['times']) || empty($project['times'])) continue;
						?>
						<li>
							<?= $project['name']?>
							<ul>
							<?php foreach ($project['times'] as $time_id => $time) {
								if ($time['state'] >= 60) continue;
							?>
								<li>
									<label>
										<input type="checkbox" name="times[<?= $time_id?>]" /><?= $time['subject']?>
										<span class="user"><?= $users[$time['user_id']]['login']?></span>
										<span class="duration">(<?= round(($time['end_time']-$time['start_time'])/3600,2)?>&nbsp;<?= t('hours')?>)</span>
										<span class="description"><?= $time['description']?></span>
										<ul>
										<?php foreach ($time['tasks'] as $task_id => $task) {
											if (!isset($tasks[$task_id])) continue; ?>
											<li><?= $tasks[$task_id]['name']?></li>
										<?php } ?>
										</ul>
									</label>
								</li>
							<?php }?>
							</ul>
						</li>
						<?php } // foreach project?>
					</ul>
				</li>
			<?php }?>
			<?php if ($items) { ?>
				<li>
					<?= t('Items')?>
					<ul>
						<?php foreach ($items as $item_id => $item) {?>
						<li>
							<label>
								<input type="checkbox" name="items[<?= $item_id?>]" />
								<span class="code"><?= $item['code']?></span>
								<span class="name"><?= $item['name']?></span>
								<span class="description"><?= str_replace("\n",'<br/>',$item['description']) ?></span>
							</label>
						</li>
						<?php } // foreach project?>
					</ul>
				</li>
			<?php }?>
			</ul>
		</fieldset>
		<fieldset>
			<legend>
				<?= t('Footer text')?>
			</legend>
			<textarea name="document[footer]"><?= $document->footer ?></textarea>
		</fieldset>
		<fieldset class="bank_account">
			<legend>
				<?= t('Bank account')?>
			</legend>
			<textarea name="document[bank_account]"><?= $document->bank_account ?></textarea>
		</fieldset>
		<fieldset class="court">
			<legend>
				<?= t('Local court')?>
			</legend>
			<input name="document[court]" value="<?= $document->court ?>"/>
		</fieldset>
		<fieldset class="template">
			<legend>
				<?= t('Template')?>
			</legend>
			<select name="document[template_id]">
				<option value=""><?= t('No document template selected')?></option>
				<?php foreach ($templates as $template) {?>
				<option value="<?= $template->id ?>" <?= $template->id == $document->template_id ? 'selected="true"':'' ?>><?= $template->name ?></option>
				<?php }?>
			</select>
			<a href="../templates?company=<?= $document->company_id ?>"><span class="symbol"></span><?= t('Manage templates') ?></a>
		</fieldset>
		<?php if (isset($services['bookmark'])){ ?>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input type="text" name="tags" value="<?= $bookmark ? implode(' ', $bookmark['tags']) : ''?>" />
		</fieldset>
		<?php } ?>
		<button type="submit"><?= t('Save')?></button>
		<a class="button" title="<?= t('Download/Preview PDF') ?>" href="download"><?= t('Download/Preview PDF') ?></a>
		<?php if (isset($services['files'])) { ?>
		<a class="button" title="<?= t('Store PDF within umbrella file management.')?>" href="store"><?= t('Store PDF')?></a>
		<?php } ?>
		<a class="button" title="<?= t('Send as PDF to ?.',$document->customer_email)?>" href="send"><?= t('Send to ?',$document->customer_email)?></a>
	</fieldset>
</form>
<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'document:'.$id],false,NO_CONVERSSION);

include '../common_templates/closure.php'; debug($document)?>
