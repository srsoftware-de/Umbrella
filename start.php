<?php include 'controller.php';

require_login('time');

Timetrack::startNew();
redirect('index');
