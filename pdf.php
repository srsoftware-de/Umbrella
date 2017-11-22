<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

const RIGHT=0;
const NEWLINE=1;
const DOWN=2;
const FRAME=1;
const NO_FRAME=0; // set this to 1 to enable debugging frames

require_login('invoice');

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
		$this->inTable=false;
	}
	
	function recipient(){
		$this->SetY(45);
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
		
		$this->SetY(30);
		$this->SetX(130);
		
	    // Title
	    $sender = explode("\n", $this->invoice['sender']);
	    foreach ($sender as $line){
	    	$this->Cell(70,4,utf8_decode($line),NO_FRAME,DOWN,'R');
	    }
	}
	
	function Header(){
		$x = 150;
		$dy = 5;
		
		$y = ($this->PageNo() == 1)?50:10;
		
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
		$this->Cell(20,4,$this->invoice['customer_num'],NO_FRAME,NEWLINE,'R');
		
		if ($this->inTable){
			$this->tableHead();
		}
	}
	
	
	function tableHead(){
		$this->SetFont('Arial','B',9);
		$this->pos_n_cell(null);
		$this->amount_cell(null);
		$this->unit_cell(null);
		$this->code_cell(null);
		$this->desc_cell(null);
		$this->s_pr_cell(null);
		$this->price_cell(null);
	}
	
	// Page footer
	function Footer(){
	    // Position at 1.5 cm from bottom
	    // Arial italic 8
	    $this->SetFont('Arial','',8);

	    $bank_account = str_replace("\n", ", ", $this->invoice['bank_account']);
	    $this->SetY(-15);
	    $this->Cell(0,5,utf8_decode(t('Bank account: ?',$bank_account)),NO_FRAME,NEWLINE,'L');
	    $this->Cell(0,5,utf8_decode(t('Local court: ?',$this->invoice['court'])),NO_FRAME,NEWLINE,'L');
	    
	    $this->SetY(-15);
	    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',NO_FRAME,0,'R');
	}
	
	function firstPage(){
		$this->AddPage();
		$this->recipient();
		$this->sender();
		
		$this->setY(95,1);
		$this->SetFont('Arial','',10);
		$head = explode("\n", $this->invoice['head']);
		foreach ($head as $line){
			$this->Cell(0,10,utf8_decode($line),NO_FRAME,DOWN,'L');
		}
		
	}
	
	function foot(){
		$this->SetFont('Arial','',10);
		$this->Ln();		
		$this->MultiCell(0, 10, utf8_decode($this->invoice['footer']));
	}
	
	function generate(){
		$this->AliasNbPages();
		$this->firstPage();
		$this->tableHead();
		$this->positions();
		$this->foot();
		$this->Output();		
	}
	
	function pos_n_cell($i){
		if ($i===null) $i = t('Pos');
		$this->Cell(10,7,utf8_decode($i),NO_FRAME,RIGHT,'R');
	}
	
	function amount_cell($i){
		$i = ($i===null)?t('Amount'):round($i,2);
		$this->Cell(12,7,utf8_decode($i),NO_FRAME,RIGHT,'R');
	}
	
	function unit_cell($i){
		if ($i===null) $i=t('Unit');
		$this->Cell(15,7,utf8_decode(t($i)),NO_FRAME,RIGHT,'L');
	}
	
	function code_cell($i){
		if ($i===null) $i=t('Code');
		$this->Cell(15,7,utf8_decode($i),NO_FRAME,RIGHT,'C');
	}
	
	function desc_cell($t,$d){
		if ($t===null) {
			$this->Cell(98,7,utf8_decode(t('Description')),NO_FRAME,'L');
		} else {
			$x = $this->GetX();
			$this->MultiCell(98,7,utf8_decode($t),NO_FRAME,'L');
			$this->SetX($x);
			$this->MultiCell(98,4,utf8_decode($d),NO_FRAME,'L');
		}
	}
	
	function s_pr_cell($i){
		$i=($i===null)?t('Single price'):round(($i/100),2);
		$this->Cell(20,7,utf8_decode($i),NO_FRAME,RIGHT,'R');
	}
	
	function price_cell($i){
		$i=($i===null)?t('Price'):round(($i/100),2);
		$this->Cell(20,7,utf8_decode($i),NO_FRAME,NEWLINE,'R');
	}
	
	function positions(){

		$this->inTable=true;
		
		$this->SetFont('Arial','',9);
		$sum = 0;
		$taxes = array();
		foreach ($this->invoice['positions'] as $pos => $position){
			$this->pos_n_cell($pos);
			$this->amount_cell($position['amount']);			
			$this->unit_cell($position['unit']);							
			$this->code_cell($position['item_code']);
			$x = $this->GetX();				
			$y = $this->GetY();
			$this->setX($x+98);
			
			$this->s_pr_cell($position['single_price']);
			$tot = $position['amount']*$position['single_price'];
			$sum += $tot;
			$this->price_cell($tot);
			$this->setY($y);
			$this->setX($x);
			$this->desc_cell($position['title'],$position['description']);
			
			$tax = $position['tax'];
			if ($tax){
				if (!isset($taxes[$tax])) $taxes[$tax]=0;
				$taxes[$tax] += $tot*$tax/100;
			}			
		}
		
		
		$this->Cell(40+93+25+12,5,utf8_decode(t('Net sum')),NO_FRAME,RIGHT,'R');
		$this->price_cell($sum);
		
		foreach ($taxes as $percent => $tax){
			$this->Cell(40+93+25+12,5,utf8_decode(t('Tax ?%',$percent)),NO_FRAME,RIGHT,'R');
			$this->price_cell($tax);
			$sum += $tax;
		}
		
		$this->SetFont('Arial','B',9);		
		
		$this->Cell(40+93+25+12,7,utf8_decode(t('Gross sum')),NO_FRAME,RIGHT,'R');
		$this->price_cell($sum);
		$this->inTable=false;
	}
	
}

// Instanciation of inherited class
$pdf = new PDF($invoice);
$pdf->generate();

?>