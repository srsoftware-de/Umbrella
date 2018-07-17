<?php
	include('pdf.php');

	// Instanciation of inherited class
	$pdf = new PDF($document);
	$pdf->generate();
	$pdf->download();
?>