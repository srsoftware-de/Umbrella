<?php

include '../bootstrap.php';
include 'controller.php';

$title = t('Umbrella: Document Management');
require_login('document');

$options = [];
if ($order = param('order')) $options['order'] = $order;

$documents = Document::load($options);
$doc_types = DocumentType::load();
$companies = request('company','json');



include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<br/>
<?php foreach ($companies as $cid => $company){ ?>
<fieldset class="document list">
	<legend><?= $company['name']?></legend>
	<a href="add?company=<?= $cid?>"><?= t('add document') ?></a>
	<table class="documents">
		<tr>
			<th><a href="?order=number"><?= t('Number') ?></a></th>
			<th><?= t('Sum')?></th>
			<th><a href="?order=date"><?= t('Date') ?></a></th>
			<th><a href="?order=state"><?= t('State') ?></a></th>
			<th><a href="?order=customer"><?= t('Customer') ?></a></th>
			<th><a href="?order=type_id"><?= t('Document type') ?></a></th>
			<th><?= t('Actions')?></th>
		</tr>
		<?php foreach ($documents as $id => $document){
			if ($document->company_id != $cid) continue;

			$type = $doc_types[$document->type_id];
			
			$successor = $type->next_type_id ? $doc_types[$type->next_type_id] : null;
			$successors = null;
			if ($successor) {
				$successors = [];
				while ($successor){
					$successors[$successor->id] = $successor;
					$successor = ($successor->next_type_id !== null && $successor->next_type_id != $successor->id) ? $doc_types[$successor->next_type_id] : null;
				}
			}
			
			$next_type_id = $doc_types[$document->type_id]->next_type_id;
			$next_type = $next_type_id ? $doc_types[$next_type_id] : null;
			?>
		<tr>
			<td><a href="<?= $document->id ?>/view"><?= $document->number ?></a></td>
			<td><a href="<?= $document->id ?>/view"><?= $document->sum().' '.$document->currency ?></a></td>
			<td><a href="<?= $document->id ?>/view"><?= $document->date() ?></a></td>
			<td><a href="<?= $document->id ?>/view"><?= t($document->state()) ?></a></td>
			<td><a href="<?= $document->id ?>/view"><?= $document->customer_short()?></a></td>
			<td><a href="<?= $document->id ?>/view"><?= t($doc_types[$document->type_id]->name) ?></a></td>
			<td><?php if ($document->state != Document::STATE_PAYED) { ?>
				<form method="POST" action="<?= $document->id ?>/step">
					<select name="type">
					<?php foreach ($successors as $succ) { ?>
						<option value="<?= $succ->id ?>"><?= t($succ->name) ?></option>
					<?php }?>
					</select>
					<button type="submit"><?= t('create')?></button>
				</form>
				<?php } ?>				
			</td>
		</tr>
		<?php } ?>
		
	</table>
</fieldset>
<?php }
include '../common_templates/closure.php';?>
