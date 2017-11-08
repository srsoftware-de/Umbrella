<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');
$urls = get_new_urls(30);
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Latest Urls')?></legend>
<?php foreach ($urls as $hash => $url){ 
	$host = $url['url'];
	$pos = strpos($host, '://');
	$pos = strpos($host, '/',$pos+3);
	$host = substr($host,0,$pos);
	
	?>
	<fieldset>
		<legend><a class="symbol" href="../<?= $hash ?>/edit"></a><a class="symbol" href="../<?= $hash ?>/delete"></a> <?= t('Site: ?',$host) ?></legend>
		<?php if (isset($url['comment'])) { ?>
		<a href="<?= $url['url']?>"><?= $url['comment']?></a><br/>
		<?php } // comment is set?>
		<a href="<?= $url['url']?>"><?= $url['url']?></a>
		<div class="tags">
		<?php foreach ($url['tags'] as $tag) { ?>		
			<a class="button" href="<?= urlencode($tag).'/view' ?>"><?= $tag ?></a>
		<?php } ?>
		</div>
	</fieldset>
<?php } ?>
</fieldset>

<?php include '../common_templates/closure.php'; ?> 