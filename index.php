<?php

include '../bootstrap.php';
include 'controller.php';

$title = t('Umbrella: Document Management');
require_login('document');
$documents = Document::load();
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
			<th><?= t('Number')?></th>
			<th><?= t('Sum')?></th>
			<th><?= t('Date')?></th>
			<th><?= t('State')?></th>
			<th><?= t('Customer')?></th>
			<th><?= t('Actions')?></th>
		</tr>
		<?php foreach ($documents as $id => $document){
			if ($document->company_id != $cid) continue; 
			$next_type_id = $doc_types[$document->type_id]->next_type_id;
			$next_type = $next_type_id ? $doc_types[$next_type_id] : null;
			?>
		<tr>
			<td><a href="<?= $document->id ?>/view"><?= $document->number ?></a></td>
			<td><a href="<?= $document->id ?>/view"><?= $document->sum().' '.$document->currency ?></a></td>
			<td><a href="<?= $document->id ?>/view"><?= $document->date() ?></a></td>
			<td><a href="<?= $document->id ?>/view"><?= t($document->state()) ?></a></td>
			<td><a href="<?= $document->id ?>/view"><?= $document->customer_short()?></a></td>
			<td><?php if ($document->state != Document::STATE_PAYED) { ?>
				<a href="<?= $document->id ?>/step"><?= t('add ?',$next_type->name)?></a>
				<?php } ?>
			</td>
		</tr>
		<?php } ?>
		
	</table>
</fieldset>
<?php }

include '../common_templates/closure.php';?>
