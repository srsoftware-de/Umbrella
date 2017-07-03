<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login();

$id = param('id');
$contact = read_contacts($id);
assert($contact !== null,'Was not able to lod this vcard from the database');
$vcard = reset($contact);
header('Content-Type: text/vcard');
header('Content-Disposition: attachment; filename="contact_'.$id.'.cvf"');
print_r(serialize_vcard($vcard));