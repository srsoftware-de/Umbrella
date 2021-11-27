<?php include 'controller.php';

require_login('document');

$db = get_or_create_db();

update($db);

redirect(getUrl('project'));