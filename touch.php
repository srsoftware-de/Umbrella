<?php include 'controller.php';

require_login('notes');

$note = Note::load(['ids' => param('id')])->patch(['timestamp'=>time()])->save(); // update timestamp

redirect($note->url()); // redirect to referencing object
