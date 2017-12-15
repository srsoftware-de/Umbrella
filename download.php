<?php 
	include('pdf.php');
	
	// Instanciation of inherited class
	$pdf = new PDF($invoice);
	$pdf->generate();	
	$pdf->download();
?>