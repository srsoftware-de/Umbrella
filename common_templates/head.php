<html>
	<head>
		<title><?= isset($title)?$title:'Umbrella' ?></title>
		<link rel="stylesheet" type="text/css" href="../common_templates/css/<?= isset($theme)?$theme:'comfort' ?>/style.css" />
		<link rel="stylesheet" type="text/css" href="../common_templates/css/<?= isset($theme)?$theme:'comfort' ?>/colors.css" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<script src="common_templates/js/jquery-3.2.1.min.js"></script>
		<script src="common_templates/js/umbrella.js"></script>
	</head>
<body class="<?= trim(str_replace('/',' ',$_SERVER['REDIRECT_URL'])); ?>">
<img id="logo" src="<?= getUrl('user','common_templates/umbrella100px.png') ?>" />