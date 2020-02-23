<?php include 'controller.php';

$user = User::require_login();

die($_SESSION['token']);
