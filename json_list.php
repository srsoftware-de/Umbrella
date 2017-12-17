<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login('contact');
echo json_encode(read_contacts());
