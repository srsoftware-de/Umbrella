<?php $title = 'Umbrella Time Tracking';

include '../bootstrap.php';
include 'controller.php';
require_login('time');

Timetrack::load(['ids'=>param('id')])->patch(['state'=>TIME_STATUS_COMPLETE])->save();
redirect('..');