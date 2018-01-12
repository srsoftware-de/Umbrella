<?php 
	include('pdf.php');
	
	$pdf = new PDF($invoice);
	$pdf->generate();

	$reciever = post('reciever',$invoice->customer_email);
	$sender = post('sender',$invoice->company()['email']);
	$subject = post('subject',t('New document from ?',$invoice->company()['name']));
	$text = post('text',$invoice->mail_text());
	
	if (isset($_POST['reciever'])){
		if ($pdf->send($sender,$reciever,$subject,$text)){
			info('Your email to ? has been sent.',$invoice->customer_email);
			$invoice->update_mail_text($text);
		} else {
			error('Was not able to send mail to ?',$invoice->customer_email);			
		}		
	}
	
	
		
	include '../common_templates/head.php';
	include '../common_templates/main_menu.php';
	include 'menu.php';
	include '../common_templates/messages.php';
	
	
?>
<form method="POST">
<fieldset>
	<legend><?= t('Send ? via mail',$invoice->number) ?></legend>
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
