<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login('contact');

if ($id = param('id')){
	VCard::load(['ids'=>$id])->assign_with_current_user();
	redirect('../index');
}