<?php include 'controller.php';

$user = User::require_login();

print(preview());