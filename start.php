<?php $title = 'Umbrella Time Tracking';

include '../bootstrap.php';
include 'controller.php';
require_login('time');

Timetrack::startNew();
redirect('index');
