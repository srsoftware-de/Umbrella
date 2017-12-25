<?php $title = 'Umbrella Notes Management';

include '../bootstrap.php';
include 'controller.php';

require_login('notes');

$notes = Note::load();
debug($notes);
