<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login('contact');
$vcards = VCard::load();
echo json_encode($vcards);
