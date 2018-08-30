<?php include 'controller.php';

require_login('document');

$id = param('id');
assert(is_numeric($id),'No valid document id passed to edit!');
$document = Document::load(['ids'=>$id]);
if (!$document) error('No document found or accessible for id ?',$id);

if (empty($document->customer_number)){
	$vcard = $document->get_customer_vcard();
	
	if ($vcard !== null){		
		if (empty($vcard->{'X-CUSTOMER-NUMBER'})){
			$company = request('company','json',['ids'=>$document->company_id]);
			if ($company !== null) set_customer_number($vcard, $company);
		}
		$document->customer_number = $vcard->{'X-CUSTOMER-NUMBER'};		
	}	
}

$new_doc = $document->derive(param('type'));
redirect('../'.$new_doc->id.'/view');
