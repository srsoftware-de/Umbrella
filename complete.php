<?php include 'controller.php';

require_login('time');

Timetrack::load(['ids'=>param('id')])->patch(['state'=>TIME_STATUS_COMPLETE])->save();
redirect('..');