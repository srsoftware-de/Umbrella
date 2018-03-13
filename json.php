<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login('contact');
$options = [];
if (param('assgined',false) == true) $options['assigned']=true;
echo json_encode(VCard::load($options));
