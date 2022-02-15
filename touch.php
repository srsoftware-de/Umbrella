<?php include 'controller.php';

require_login('notes');

$note = Note::load(['ids' => param('id')])->patch(['timestamp'=>time()])->save(); // update timestamp

redirect(url($note->uri)); // redirect to referencing object
