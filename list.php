<?php
$map = []; 
foreach ($bookmarks as $hash => $bookmark) {
	$type = empty($bookmark->internal) ? 'external' : $bookmark->internal;
	$map[$type][$hash] = $bookmark;
} 
foreach ($services as $service => $sdata){
	if (isset($map[$service])){?>
<fieldset class="<?= $service ?>">
	<legend><?= t($sdata['name']) ?></legend>
	<?php foreach ($map[$service] as $hash => $bookmark) { $bookmark->comment() ?>
	<span class="hover_h">
		<a class="button" href="<?= $bookmark->url ?>"><?= $bookmark->comment() ? $bookmark->comment->comment : $bookmark->url ?></a>
		<a class="symbol" href="<?= $base_url.$hash ?>/edit?returnTo=<?= urlencode(location('*'))?>"></a>
		<a class="symbol" href="<?= $base_url.$hash ?>/delete?returnTo=<?= urlencode(location('*'))?>"></a>
		<a><br/><br/><?= t('Tags:') ?></a>
		<?php foreach ($bookmark->tags() as $tag){ ?>		
		<a class="button" href="<?= $base_url.$tag->tag.'/view' ?>"><?= $tag->tag ?></a>
		<?php } ?>
		<a><br/><br/></a>
	</span><br/>
	<?php } ?>
</fieldset>
<?php   }
}

if (!empty($map['external'])) { ?>
<fieldset class="bookmark">
	<legend><?= $legend ?></legend>
	<?php foreach ($map['external'] as $hash => $bookmark ) {	    
	    if ($bookmark->comment()){
	        $parts = explode("\n", $bookmark->comment()->comment,2);
	    }
	    $url = strlen($bookmark->url) > 100 ? substr($bookmark->url, 0, 99) . '…' : $bookmark->url;
	    ?>
	<fieldset>
		<legend>
			<a class="symbol" href="<?= $base_url.$hash ?>/edit?returnTo=<?= urlencode(location('*'))?>"></a>
			<a class="symbol" href="<?= $base_url.$hash ?>/delete?returnTo=<?= urlencode(location('*'))?>"></a>
			<a <?= empty($bookmark->internal)?'target="_blank"':''?> href="<?= $bookmark->url ?>" ><?= $parts ? $parts[0] : $bookmark->url ?> | <?= date('Y-m-d H:i',$bookmark->timestamp) ?></a>
		</legend>
		<a <?= empty($bookmark->internal)?'target="_blank"':''?> href="<?= $bookmark->url ?>" ><?= $url ?></a>
		<?= $parts ? markdown($parts[1]) : "" ?>
		<div class="tags">
			<?php foreach ($bookmark->tags() as $tag){ ?>
			<a class="button" href="<?= $base_url.$tag->tag.'/view' ?>"><?= $tag->tag ?></a>
			<?php } ?>
		</div>
		<fieldset class="share">
			<legend><?= t('Share bookmark')?></legend>
			<form method="POST">
				<input type="hidden" name="share_url_hash" value="<?= $hash?>" />
				<input type="hidden" name="notify" value="1" />
				<select name="share_user_id">
				<option value=""><?= t('select user')?></option>
				<?php foreach ($users as $uid => $some_user) {
					if ($uid == $user->id) continue; ?>
				<option value="<?= $uid?>"><?= $some_user['login'] ?></option>
				<?php } ?>
				</select>
				<button type="submit"><?= t('share')?></button>
			</form>
		</fieldset>
	</fieldset>
	<?php } ?>
</fieldset>
<?php } // if external not empty ?>
