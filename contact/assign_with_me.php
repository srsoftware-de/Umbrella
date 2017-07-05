<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login();

assign_contact(param('id'));
redirect('../index');