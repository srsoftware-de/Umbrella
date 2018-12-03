<?php
	$hash = isset($_ENV['COMMIT_HASH']) ? '?hash='.$_ENV['COMMIT_HASH'] : '';
	$base = getUrl('user');
?>
<html>
	<head>
		<title><?= isset($title)?$title:'Umbrella' ?></title>
		<link rel="stylesheet" type="text/css" href="<?= $base ?>common_templates/css/<?= isset($theme)?$theme:'comfort' ?>/style.css<?= $hash ?>" />
		<link rel="stylesheet" type="text/css" href="<?= $base ?>common_templates/css/<?= isset($theme)?$theme:'comfort' ?>/colors.css<?= $hash ?>" />
		<link rel="stylesheet" type="text/css" href="<?= $base ?>common_templates/css/<?= isset($theme)?$theme:'comfort' ?>/svg_colors.css<?= $hash ?>" />
		<link rel="stylesheet" type="text/css" href="<?= $base ?>common_templates/css/svg_common.css<?= $hash ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<script src="<?= $base ?>common_templates/js/jquery-3.2.1.min.js"></script>
		<script src="<?= $base ?>common_templates/js/umbrella.js<?= $hash ?>"></script>
	</head>
<body class="<?= trim(str_replace('/',' ',$_SERVER['REDIRECT_URL'])); ?>">
<img id="logo" src="<?= getUrl('user','common_templates/umbrella100px.png') ?>" />