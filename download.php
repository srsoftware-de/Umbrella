<?php include 'controller.php';

require_login('contact');

if ($id = param('id')){
	$vcard = VCard::load(['ids'=>$id]);
	header('Content-Type: text/vcard');
	header('Content-Disposition: attachment; filename="contact_'.$id.'.cvf"');
	print_r($vcard->format());
} else {
	echo 'No id set!';
}