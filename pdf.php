<?php include 'controller.php';

require_login('document');

const RIGHT=0;
const NEWLINE=1;
const DOWN=2;
const FRAME=1;
const NO_FRAME=0; // default 0, set this to 1 to enable debugging frames

require_login('document');

$id = param('id');
if (!is_numeric($id)) throw new Exception('No valid document id passed to edit!');
$document = Document::load(['ids'=>$id]);
if ($document == null) throw new Exception('No document found or accessible for id = '.$id);

require('lib/fpdf181/fpdf.php');

class PDF extends FPDF{
	function __construct($document){
		parent::__construct('P','mm','A4');
		$this->document = $document;
		$this->inTable=false;
	}

	function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link=''){
		parent::Cell($w, $h, utf8_decode($txt), $border, $ln, $align, $fill, $link);
	}

	function logo(){
		if ($template = $this->document->template()){

			$file = $template->file();
			$type = end(explode('/',mime_content_type($file)));
			$this->Image($file,10,10,null,30,$type);
		}
	}

	function recipient(){
		$this->SetY(45);
		$this->SetX(10);
		$this->SetFont('Arial','U',7);

		$sender = str_replace(["\r\n","\n"], ', ', $this->document->sender);
		$this->Cell(0,8,$sender,NO_FRAME,DOWN,'L');

		$this->SetFont('Arial','',10);
		$customer = explode("\n", str_replace("\r\n","\n",$this->document->customer));
		foreach ($customer as $line){
			$this->Cell(0,5,$line,NO_FRAME,DOWN,'L');
		}
	}

	function sender(){
		$this->SetFont('Arial','',8);

		$this->SetY(30);
		$this->SetX(130);

		// Title
		$sender = explode("\n", str_replace("\r\n","\n",$this->document->sender));
		foreach ($sender as $line){
			$this->Cell(70,4,$line,NO_FRAME,DOWN,'R');
		}
		$this->Cell(70,4,$this->document->company('phone'),NO_FRAME,DOWN,'R');
		$this->Cell(70,4,$this->document->company('email'),NO_FRAME,DOWN,'R');
	}

	function Header(){
		$x = 150;
		$dy = 5;

		$y = ($this->PageNo() == 1)?50:10;

		$this->SetFont('Arial','B',8);

		$this->SetXY($x,$y=$y+$dy);
		$this->Cell(30,4,t($this->document->type()->name.' number'),NO_FRAME,RIGHT,'L');

		$this->Cell(20,4,$this->document->number,NO_FRAME,RIGHT,'R');

		$this->SetFont('Arial','',8);

		$date = date(t('Y-m-d'),$this->document->date);
		$this->SetXY($x,$y=$y+$dy);
		$this->Cell(30,4,t('Date'),NO_FRAME,RIGHT,'L');
		$this->Cell(20,4,$date,NO_FRAME,RIGHT,'R');

		$this->SetXY($x,$y=$y+$dy);
		$this->Cell(30,4,t('Delivery Date'),NO_FRAME,RIGHT,'L');
		$this->Cell(20,4,$this->document->delivery_date(),NO_FRAME,RIGHT,'R');

		$this->SetXY($x,$y=$y+$dy);
		$this->Cell(30,4,t('Tax number'),NO_FRAME,RIGHT,'L');
		$this->Cell(20,4,$this->document->tax_number,NO_FRAME,RIGHT,'R');

		$this->SetXY($x,$y=$y+$dy);
		$this->Cell(30,4,t('Customer number'),NO_FRAME,RIGHT,'L');
		$this->Cell(20,4,$this->document->customer_number,NO_FRAME,NEWLINE,'R');

		if ($this->document->customer_tax_number){
			$this->SetXY($x,$y=$y+$dy);
			$this->Cell(30,4,t('Customer tax number'),NO_FRAME,RIGHT,'L');
			$this->Cell(20,4,$this->document->customer_tax_number,NO_FRAME,NEWLINE,'R');
		}

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
		$this->desc_cell(null,'');
		$this->s_pr_cell(null);
		$this->price_cell(null);
	}

	// Page footer
	function Footer(){
		// Position at 1.5 cm from bottom
		// Arial italic 8
		$this->SetFont('Arial','',8);

		$bank_account = str_replace(["\r\n","\n"], ", ", $this->document->bank_account);
		$this->SetY(-15);
		$this->Cell(0,5,t('Bank account: ◊',$bank_account),NO_FRAME,NEWLINE,'L');
		$this->Cell(0,5,t('Local court: ◊',$this->document->court),NO_FRAME,NEWLINE,'L');

		$this->SetY(-15);
		$this->Cell(0,10,t('Page ◊/◊',[$this->PageNo(),'{nb}']),NO_FRAME,0,'R');
		$this->Ln();
	}

	function firstPage(){
		$this->AddPage();

		$this->logo();

		$this->recipient();
		$this->sender();

		$this->setY(95,1);
		$this->SetFont('Arial','',10);
		$this->MultiCell(0, 5, $this->document->head);
	}

	function foot(){
		$this->SetFont('Arial','',10);
		$this->Ln();
		$this->MultiCell(0, 5, $this->document->footer);
	}

	function generate(){
		$this->AliasNbPages();
		$this->firstPage();
		$this->tableHead();
		$this->positions();
		$this->foot();
	}

	function download(){
		$this->Output('I',$this->document->number.'.pdf');
	}

	function store($dir){
		$file_contents = $this->Output('S');
		$filename = $this->document->number.' - '.date('c').'.pdf';
		save_file($dir.'/'.$filename,$file_contents,'application/pdf');
		$list = request('files','index',['format'=>'json','path'=>$dir]);
		if (!array_key_exists($filename, $list['files'])) throw new Exception('Something went wrong with the file upload!');
		redirect(getUrl('files','index?path='.urlencode($dir)));
	}

	function send($sender,$reciever,$subject,$text){
		global $user;
		if ($reciever == ''){
			error('No reciever set for mail!');
			return false;
		}
		if ($sender == ''){
			error('No sender set for mail!');
			return false;
		}
		if ($subject == ''){
			error('No subject set for mail!');
			return false;
		}
		if ($text == ''){
			error('No text set for mail!');
			return false;
		}

		$attachment = [
			'name' => $this->document->number.' - '.date('c').'.pdf',
			'content' => $this->Output('S'),
		];

		$recievers = [ $sender, $reciever ]; // send mail to both reciever and sender

		return send_mail($sender,$recievers,$subject,$text,$attachment);
	}

	function pos_n_cell($i){
		if ($i===null) $i = t('Pos');
		$this->Cell(10,7,$i,NO_FRAME,RIGHT,'R');
	}

	function amount_cell($i){
		$i = ($i===null)?t('Amount'):round($i,2);
		$this->Cell(12,7,$i,NO_FRAME,RIGHT,'R');
	}

	function unit_cell($i){
		if ($i===null) $i=t('Unit');
		$this->Cell(15,7,t($i),NO_FRAME,RIGHT,'L');
	}

	function code_cell($i){
		if ($i===null) $i=t('Code');
		$this->Cell(20,7,$i,NO_FRAME,RIGHT,'C');
	}

	function desc_cell($t,$d){
		if ($t===null) {
			$this->Cell(93,7,t('Description'),NO_FRAME,'L');
		} else {
			$x = $this->GetX();
			$this->MultiCell(93,7,$t,NO_FRAME,'L');
			$this->SetX($x);
			$this->MultiCell(93,4,$d,NO_FRAME,'L');
		}
	}

	function format_value($v){
		return number_format($v/100,2,$this->document->company('decimal_separator'),$this->document->company('thousands_separator'));
	}

	function s_pr_cell($i){
		$i=($i===null)?t('Single price'):$this->format_value($i);
		$this->Cell(20,7,$i,NO_FRAME,RIGHT,'R');
	}

	function price_cell($i,$note = null){
		if ($i === null) $i = t('Price');
		if (is_numeric($i)) $i = $this->format_value($i);
		$x = $this->GetX();
		$this->Cell(20,7,$i,NO_FRAME,NEWLINE,'R');
		if (!empty($note)){
			$this->SetX($x);
			$this->Cell(20,0,$note,NO_FRAME,NEWLINE,'R');
		}
	}

	function positions(){
		$debug = param('debug','false') == 'true';
		$this->inTable=true;

		$this->SetFont('Arial','',9);
		$sum = 0;
		$taxes = array();
		foreach ($this->document->positions() as $pos => $position){
			$str = 'Pos '.$pos.': ';
			$this->pos_n_cell($pos);
			$this->amount_cell($position->amount);
			$this->unit_cell($position->unit);
			$this->code_cell($position->item_code);
			$x = $this->GetX();
			$y = $this->GetY();
			$this->setX($x+93);

			$str .= $position->amount . ' x ' . $position->single_price . ' = ';

			$this->s_pr_cell($position->single_price);
			$tot = $position->amount*$position->single_price;
			$str .= $tot. ', ';

			$this->price_cell($tot,$position->optional?'optional':null);
			$this->setY($y);
			$this->setX($x);
			$this->desc_cell($position->title,$position->description);

			$tax = $position->tax;
			$str .= $tax. '% Ust. = '.$tot*$tax/100;

			if ($position->optional) {} else {
				$sum += $tot;
				if ($tax){
					if (!isset($taxes[$tax])) $taxes[$tax]=0;
					$taxes[$tax] += $tot*$tax/100;
				}
			}
			if ($debug) debug($str);
		}

		if ($debug) debug('Netto: '.$sum);

		$this->Cell(40+93+25+12,5,t('Net sum'),NO_FRAME,RIGHT,'R');
		$this->price_cell($sum);

		foreach ($taxes as $percent => $tax){
			$this->Cell(40+93+25+12,5,t('Tax ◊%',$percent),NO_FRAME,RIGHT,'R');
			if ($debug) debug($percent.'% Ust: '.$tax);
			$this->price_cell($tax);
			$sum += $tax;
		}

		if ($debug) debug('Summe: '.$sum,1);
		$this->SetFont('Arial','B',9);

		$this->Cell(40+93+25+12,7,t('Gross sum'),NO_FRAME,RIGHT,'R');
		$this->price_cell($sum);
		$this->inTable=false;
	}
}

?>
