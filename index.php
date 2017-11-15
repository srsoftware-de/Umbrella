<?php $title = 'Umbrella Bookmark Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');
$urls = get_new_urls(param('id',20)); // latest => show 20, latest/15 => show 15
$base_url = getUrl('bookmark');
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
		<legend>
			<a class="symbol" title="<?= t('edit bookmark') ?>" href="<?= $base_url.$hash ?>/edit"></a>
			<a class="symbol" title="<?= t('delete bookmark') ?>" href="<?= $base_url.$hash ?>/delete"></a>
			<?= t('Site: ?',$host) ?>
		</legend>
		<?php if (isset($url['comment'])) { ?>
		<a href="<?= $url['url']?>" target="_blank"><?= $url['comment']?></a><br/>
		<?php } // comment is set?>
		<a href="<?= $url['url']?>" target="_blank"><?= $url['url']?></a>
		<div class="tags">
		<?php foreach ($url['tags'] as $tag) { ?>		
			<a class="button" href="<?= $base_url.urlencode($tag).'/view' ?>"><?= $tag ?></a>
		<?php } ?>
		</div>
	</fieldset>
<?php } ?>
</fieldset>

<?php include '../common_templates/closure.php'; ?> 
