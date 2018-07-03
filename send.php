<?php
	include('pdf.php');

	$pdf = new PDF($document);
	$pdf->generate();

	$reciever = post('reciever',$document->customer_email);
	$sender = post('sender',$document->company()['email']);
	$subject = post('subject',t('New '.$document->type->name.' from ?',$document->company()['name']));
	$text = post('text',$document->mail_text());

	if (isset($_POST['reciever'])){
		if ($pdf->send($sender,$reciever,$subject,$text)){
			info('Your email to ? has been sent.',$document->customer_email);
			$document->update_mail_text($text);
			if ($document->state == Document::STATE_NEW){
				$document->patch(['state'=>Document::STATE_SENT]);
				$document->save();
			}
			redirect('view');
		} else {
			error('Was not able to send mail to ?',$document->customer_email);
		}
	}

	if ($document->customer_number == '') warn('No customer number set in document!');
	if ($document->delivery_date() == '') warn('No delivery date set in document!');
	if ($document->template_id == null) warn('No document template selected!');
	
	include '../common_templates/head.php';
	include '../common_templates/main_menu.php';
	include 'menu.php';
	include '../common_templates/messages.php';

?>
<form method="POST">
<fieldset>
	<legend><?= t('Send ? via mail',$document->number) ?></legend>
	<fieldset>
		<legend><?= t('Reciever')?></legend>
		<input type="text" name="reciever" value="<?= $reciever ?>" />
	</fieldset>
	<fieldset>
		<legend><?= t('Sender')?></legend>
		<input type="text" name="sender" value="<?= $sender?>" />
	</fieldset>
	<fieldset>
		<legend><?= t('Subject')?></legend>
		<input type="text" name="subject" value="<?= $subject; ?>">
	</fieldset>
	<fieldset>
		<legend><?= t('Text')?></legend>
		<textarea name="text"><?= $text ?></textarea>
	</fieldset>
	<input type="submit" />
	<a class="button" href="view"><?= t('Go back') ?></a>
</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
