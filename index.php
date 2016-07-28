<?php

session_start();

if (!isset($_SESSION['token'])){
	include('user/forms/login');
	die();
}
die();
