<?php include 'controller.php';

require_login('notes');

if ($uri = param('id')){
	if ($new_uri = param('new')){
		$notes = Note::load(['uri'=>$uri]);
		foreach ($notes as $note) $note->patch(['uri'=>$new_uri])->save();
	} else error('notes/'.$uri.'/update_uri requires you to pass a new uri!');
} else {
	error('notes/update_uri requires you to pass an uri!');
}