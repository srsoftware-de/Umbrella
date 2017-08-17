<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

const RIGHT=0;
const NEWLINE=1;
const DOWN=2;
const FRAME=1;
const NO_FRAME=0;

require_login();

$id = param('id');
assert(is_numeric($id),'No valid invoice id passed to edit!');
$invoice = load_invoices($id);
assert($invoice !== null,'No invoice found or accessible for id = '.$id);
load_positions($invoice);
//debug($invoice,1);

require('lib/fpdf181/fpdf.php');

class PDF extends FPDF{
	function __construct($invoice){
		parent::__construct('P','mm','A4');
		$this->invoice = $invoice;
	}
	
	function recipient(){
		$this->SetY(80);
		$this->SetX(10);
		$this->SetFont('Arial','U',7);
		
		$sender = str_replace("\n", ', ', $this->invoice['sender']);
		$this->Cell(0,8,utf8_decode($sender),NO_FRAME,DOWN,'L');
		
		$this->SetFont('Arial','',10);
		$customer = explode("\n", $this->invoice['customer']);
		foreach ($customer as $line){
			$this->Cell(0,5,utf8_decode($line),NO_FRAME,DOWN,'L');
		}
	}
	
	function sender(){
		$this->SetFont('Arial','',8);
		
		$this->SetY(60);
		$this->SetX(130);
		
	    // Title
	    $sender = explode("\n", $this->invoice['sender']);
	    foreach ($sender as $line){
	    	$this->Cell(70,4,utf8_decode($line),NO_FRAME,DOWN,'R');
	    }
	}
	
	function dates(){
		$x = 150;
		$y = 80;
		$dy = 5;
		
		$this->SetFont('Arial','B',8);
		
		$date = date(t('Y-m-d'),$this->invoice['delivery_date']);
		$this->SetXY($x,$y=$y+$dy);
		$this->Cell(30,4,t('Invoice Number'),NO_FRAME,RIGHT,'L');
		$this->Cell(20,4,'INV0001',NO_FRAME,RIGHT,'R');
		
		$this->SetFont('Arial','',8);
		
		$date = date(t('Y-m-d'),$this->invoice['invoice_date']);
		$this->SetXY($x,$y=$y+$dy);
		$this->Cell(30,4,t('Date'),NO_FRAME,RIGHT,'L');
		$this->Cell(20,4,$date,NO_FRAME,RIGHT,'R');
		
		$date = date(t('Y-m-d'),$this->invoice['delivery_date']);
		$this->SetXY($x,$y=$y+$dy);
		$this->Cell(30,4,t('Delivery Date'),NO_FRAME,RIGHT,'L');
		$this->Cell(20,4,$date,NO_FRAME,RIGHT,'R');
		
		$date = date(t('Y-m-d'),$this->invoice['delivery_date']);
		$this->SetXY($x,$y=$y+$dy);
		$this->Cell(30,4,t('Tax number'),NO_FRAME,RIGHT,'L');
		$this->Cell(20,4,$this->invoice['tax_num'],NO_FRAME,RIGHT,'R');

		$date = date(t('Y-m-d'),$this->invoice['delivery_date']);
		$this->SetXY($x,$y=$y+$dy);
		$this->Cell(30,4,t('Customer number'),NO_FRAME,RIGHT,'L');
		$this->Cell(20,4,$this->invoice['customer_num'],NO_FRAME,RIGHT,'R');
		
	}
	
	
	// Page footer
	function Footer(){
	    // Position at 1.5 cm from bottom
	    $this->SetY(-15);
	    // Arial italic 8
	    $this->SetFont('Arial','I',8);
	    // Page number
	    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
	}
	
	function firstPage(){
		$this->AddPage();
		$this->recipient();
		$this->sender();
		
		$this->dates();
		$this->setY(120,1);
		$this->SetFont('Arial','',10);
		$head = explode("\n", $this->invoice['head']);
		foreach ($head as $line){
			$this->Cell(0,8,utf8_decode($line),NO_FRAME,DOWN,'L');
		}
		
	}
	
	function generate(){
		$this->AliasNbPages();
		$this->firstPage();
		$this->SetFont('Arial','',9);
		$this->positions();
		$this->Output();		
	}
	
	function pos_n_cell($i){
		if ($i===null) $i=t('Pos');
		//$this->Ln();
		$this->Cell(10,7,$i,NO_FRAME,RIGTH,'R');
	}
	
	function amount_cell($i){
		$i = ($i===null)?t('Amount'):round($i,2);
		$this->Cell(12,7,$i,NO_FRAME,RIGTH,'R');
	}
	
	function unit_cell($i){
		if ($i===null) $i=t('Unit');
		$this->Cell(15,7,$i,NO_FRAME,RIGTH,'L');
	}
	
	function code_cell($i){
		if ($i===null) $i=t('Code');
		$this->Cell(15,7,$i,NO_FRAME,RIGTH,'C');
	}
	
	function desc_cell($i){
		if ($i===null) $i=t('Description');
		$this->MultiCell(70,7,$i,NO_FRAME,'L');
	}
	
	function positions(){
		$this->pos_n_cell(null);
		$this->amount_cell(null);
		$this->unit_cell(null);
		$this->code_cell(null);
		$this->desc_cell(null);
		foreach ($this->invoice['positions'] as $pos => $position){
			$this->pos_n_cell($pos);
			$this->amount_cell($position['amount']);			
			$this->unit_cell($position['unit']);
			$this->code_cell($position['item_code']);
			$this->desc_cell($position['title']);
		}
	}
}

// Instanciation of inherited class
$pdf = new PDF($invoice);
$pdf->generate();

?>