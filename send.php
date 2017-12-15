<?php 
	include('pdf.php');
	
	$pdf = new PDF($invoice);
	$pdf->generate();
	if ($pdf->send()){
		info('Your email to ? has been sent.',$invoice->customer_email);
	} else {
		error('Was not able to send mail to ?',$invoice->customer_email);
	}
	
	include '../common_templates/head.php';
	include '../common_templates/main_menu.php';
	include 'menu.php';
	include '../common_templates/messages.php';

	include '../common_templates/closure.php';
?>