<?php include 'controller.php';

require_login('document');

$document_id = param('id');

$document = Document::load(['ids'=>$document_id]);

if (!$document){
	error('No document found or accessible for id ◊',$document_id);
	redirect(getUrl('document'));
}

if (!$document->can_be_deleted()) {
	error('This ◊ can not be deleted!',t($document->type()->name));
	redirect(getUrl('document',$document_id.'/view'));
}
if (param('confirm')=='yes'){
	$document->delete();
	redirect(getUrl('document'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($document){ ?>
<fieldset>
	<legend><?= t('Are you shure you want to delete ◊ ◊?',[t($document->type()->name),$document->number])?></legend>
	<a href="?confirm=yes" class="button"><?= t('Yes')?></a>
	<a href="view" class="button"><?= t('No')?></a>
</fieldset>
<?php }

include '../common_templates/closure.php';?>